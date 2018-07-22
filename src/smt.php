<?php

/**
 * @file
 * Provides main functionality.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use LucidFrame\Console\ConsoleTable;

// Variables

$home = $_SERVER['HOME'];
$path = exec('pwd');

$user_config_file = $home . '/.config/smt/smt.yml';
$current_config_file = $path . '/smt.yml';
$global = FALSE;

$project_info_file = __DIR__ . '/../composer.json';

// @todo detect environment in init()
$environment = 'macos-2.10';
require_once __DIR__ . '/../includes/' . $environment . '.inc';

// Functions

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function init() {
  // @todo detect environment
  echo '<Init>' . PHP_EOL;
  return;
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function get_config_file() {
  global $user_config_file;
  global $current_config_file;
  global $global;
  // set global option
  if ($global) {
    return $user_config_file;
  }
  // no global option, but exist config in current folder 
  elseif (file_exists($current_config_file)) {
    return $current_config_file;
  }
  return $user_config_file;
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function get_config($config_file = FALSE) {
  if (!$config_file) {
    $config_file = get_config_file();
  }
  if (file_exists($config_file)) {
    return Yaml::parseFile($config_file);
  }
  else {
    return array();
  }
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function set_config($config, $config_file) {
  $yaml = Yaml::dump($config, 4, 2);
  return file_put_contents($config_file, $yaml);
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function get_connections() {
  $config = get_config();
  if (isset($config['connections'])) {
    return $config['connections'];
  }
  else {
    return array();
  }
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function get_connection_settings($cid) {
  $config = get_config();
  return $config['connections'][$cid];
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function set_connection_settings($cid, $connection_settings, $use_current_dir = FALSE) {
  global $user_config_file;
  global $current_config_file;
  // save to current dir, no config in current dir, should not load global
  if ($use_current_dir) {
    $config = get_config($current_config_file);
  }
  else {
    $config = get_config();
  }
  $connection_exist = FALSE;
  if (isset($config['connections'])) {
    foreach ($config['connections'] as $key => $value) {
      if ($key == $cid) {
        // @todo ask for rewrite
        $config['connections'][$key] = $connection_settings;
        $connection_exist = TRUE;
      }
    }
  }
  if (!$connection_exist) {
    $config['connections'][$cid] = $connection_settings;
  }
  if ($use_current_dir) {
    $config_file = $current_config_file;
  }
  else {
    $config_file = $user_config_file;
  }
  return set_config($config, $config_file);
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function remove_connection_settings($cid) {
  $config_file = get_config_file();
  $config = get_config();
  foreach ($config['connections'] as $key => $value) {
    if ($key == $cid) {
      unset($config['connections'][$key]);
    }
  }
  return set_config($config, $config_file);
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function get_cid($mount_point) {
  global $home;
  $connections = get_connections();
  foreach ($connections as $cid => $connection_settings) {
    if ($mount_point == $connection_settings['mount']) {
      return $cid;
    }
    elseif (substr($connection_settings['mount'], 0, 1) == '~') {
      $absolute_path = $home . substr($connection_settings['mount'], 1);
      if ($mount_point == $absolute_path) {
        return $cid;
      }
    }
  }
  return FALSE;
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function match_cid($input) {
  $connections = get_connections();
  foreach ($connections as $cid => $connection_settings) {
    if ($input == $cid) {
      return $cid;
    }
  }
  return FALSE;
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function match_cmd($input) {
  global $commands;
  foreach ($commands as $cmd => $command_settings) {
    if (in_array($input, $command_settings['aliases'])) {
      return $cmd;
    }
  }
  return FALSE;
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function run_cmd($cmd, $success_message = 'Ok') {
  $output = [];
  $result_code = 0;
  $run = exec($cmd, $output, $result_code);
  if ($result_code == 0) {
    return $success_message;
  }
  // return last line if something goes wrong
  return $run;
}

/**
 * Description of function.
 * https://stackoverflow.com/questions/187736/command-line-password-prompt-in-php
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function readline_silent($prompt = '') {
  $command = "/usr/bin/env bash -c 'echo OK'";
  if (rtrim(shell_exec($command)) !== 'OK') {
    trigger_error("Can't invoke bash");
    return;
  }
  $command = "/usr/bin/env bash -c 'read -s -p \""
    . addslashes($prompt)
    . "\" mypassword && echo \$mypassword'";
  $input = rtrim(shell_exec($command));
  echo PHP_EOL;
  return $input;
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function green($text) {
  return "\033[32m" . $text . "\033[39m";
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function choose_connection($mounted_only = FALSE, $show_only = FALSE) {
  $connections = get_connections();

  // no connections
  if (empty($connections)) {
    echo 'No saved connections' . PHP_EOL;
    // not error
    exit (0);
  }

  // one connection, not just show
  if (count($connections) == 1 && !$show_only) {
    return key($connections);
  }

  $mounts = get_mounts();

  // no mounts
  if ($mounted_only) {
    if (empty($mounts)) {
      echo 'No mounted connections' . PHP_EOL;
      // not error
      exit (0);
    }
  }

  // one mount, should work automaticaly only when set only one connection 
  if ($mounted_only && count($connections) == 1) {
    if (count($mounts) == 1) {
      return $mounts[0];
    }
  }

  // multiple connections

  $i = 1;
  $cids = [];

  $table = new ConsoleTable();
  $table->setHeaders([
    '#',
    'connection',
    'status',
  ]);
  foreach ($connections as $cid => $connection_settings) {
    if (in_array($cid, $mounts)) {
      $table->addRow([
        green($i),
        green($cid),
        green('mounted'),
      ]);
      $cids[$i] = $cid;
      $i++;
    }
    elseif (!$mounted_only) {
      $table->addRow([
        $i,
        $cid,
        'not mounted',
      ]);
      $cids[$i] = $cid;
      $i++;
    }
  }
  $table->setPadding(2);
  $table->hideBorder();
  $table->display();

  if (!$show_only) {
    $input = readline('Number or name of connection: ');
    $cid = validate_input($input, $cids);
    return $cid;
  }

  exit (0);
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function show_connection_settings($connection_settings) {
  $table = new ConsoleTable();
  $table->setHeaders([
    'property',
    'value',
  ]);
  foreach ($connection_settings as $key => $value) {
    if ($key == 'options') {
      $table->addRow([
        $key,
        implode(',', $value),
      ]);
    }
    elseif ($key == 'password' && $value) {
      $table->addRow([
        $key,
        '[password]',
      ]);
    }
    else {
      $table->addRow([
        $key,
        $value,
      ]);
    }
  }
  $table->setPadding(2);
  $table->hideBorder();
  $table->display();
  return;
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function validate_input($input, $cids) {
  if (is_numeric($input)) {
    $input = intval($input);
    $cids_count = count($cids);
    if ($input > 0 && $input <= $cids_count) {
      $cid = $cids[$input];
    } else {
      echo $input . ' is not a valid connection number' . PHP_EOL;
      exit(1);
    }
  }
  else {
    if (match_cid($input)) {
      $cid = $input;
    }
    else {
      echo $input . ' is not a valid connection name' . PHP_EOL;
      exit(1);
    }
  }
  return $cid;
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function read_input($prompt, $default_value = NULL, $requred = FALSE, $silent = FALSE) {
  if ($requred) {
    $input = '';
    while (!$input) {
      if ($silent) {
        $input = readline_silent($prompt);
      }
      else {
        $input = readline($prompt);
      }
    }
  }
  else {
    if ($silent) {
      $input = readline_silent($prompt);
    }
    else {
      $input = readline($prompt);
    }
    if (!$input) {
      $input = $default_value;
    }
  }
  return $input;
}

// params definition
$params = [];

$params['arguments']['cid'] = [
  'name' => 'Connection name',
  'argument' => 'connection',
  'validate' => 'match_cid',
];

$params['arguments']['cmd'] = [
  'name' => 'Command',
  'argument' => 'command',
  'validate' => 'match_cmd',
];

// handled separately, listed here for generating help and pass validation as option
$params['options']['global'] = [
  'name' => 'Use config in user directory',
  'key' => 'g',
];

$params['options']['verbose'] = [
  'name' => 'Verbose node',
  'key' => 'v',
];

$params['options']['silent'] = [
  'name' => 'Silent mode',
  'key' => 's',
];

$params['options']['yes'] = [
  'name' => 'Automatic confirmation',
  'key' => 'y',
];

// listed here for generating help and pass validation as option
$params['options']['help'] = [
  'name' => 'Show help',
  'key' => 'h',
];

$params['flags']['password'] = [
  'name' => 'Provide password',
  'argument' => 'password',
  'key' => 'p',
];

// commands definition
$commands = [];

$commands['default'] = [
  'name' => 'Mount connection',
  'aliases' => [],
  'optional_args' => [
    'cid' => $params['arguments']['cid'],
    'password' => $params['flags']['password'],
    'global' => $params['options']['global'],
    'verbose' => $params['options']['verbose'],
    'silent' => $params['options']['silent'],
  ],
  'cmd' => 'cmd_mount', 
];

$commands['unmount'] = [
  'name' => 'Unmount connection',
  'aliases' => [
    'unmount',
    'um',
  ],
  'optional_args' => [
    'cid' => $params['arguments']['cid'],
    'global' => $params['options']['global'],
    'verbose' => $params['options']['verbose'],
    'silent' => $params['options']['silent'],
  ],
  'cmd' => 'cmd_unmount', 
];

$commands['add'] = [
  'name' => 'Add connection',
  'aliases' => [
    'add',
  ],
  'optional_args' => [
    'verbose' => $params['options']['verbose'],
  ],
  'cmd' => 'cmd_add', 
];

$commands['remove'] = [
  'name' => 'Remove connection',
  'aliases' => [
    'remove',
    'rm',
  ],
  'optional_args' => [
    'cid' => $params['arguments']['cid'],
    'global' => $params['options']['global'],
    'silent' => $params['options']['silent'],
    'yes' => $params['options']['yes'],
  ],
  'cmd' => 'cmd_remove', 
];

$commands['list'] = [
  'name' => 'List connection properties',
  'aliases' => [
    'list',
    'ls',
  ],
  'optional_args' => [
    'cid' => $params['arguments']['cid'],
    'global' => $params['options']['global'],
  ],
  'cmd' => 'cmd_list', 
];

$commands['status'] = [
  'name' => 'Show status of connections',
  'aliases' => [
    'status',
    'st',
  ],
  'optional_args' => [
    'global' => $params['options']['global'],
  ],
  'cmd' => 'cmd_status', 
];

$commands['config'] = [
  'name' => 'Open config file',
  'aliases' => [
    'config',
    'cfg',
  ],
  'optional_args' => [
    'global' => $params['options']['global'],
  ],
  'cmd' => 'cmd_config',
];

$commands['help'] = [
  'name' => 'Show help',
  'aliases' => [
    'help',
    '--help',
    '-h',
  ],
  'optional_args' => [
    'cmd' => $params['arguments']['cmd'],
  ],
  'cmd' => 'cmd_help', 
];

$commands['version'] = [
  'name' => 'Show version',
  'aliases' => [
    'version',
    '--version',
    '-V',
  ],
  'cmd' => 'cmd_version',
];

$commands['info'] = [
  'name' => 'Show information about dependencies',
  'aliases' => [
    'info',
    '--info',
    '-i',
  ],
  'cmd' => 'cmd_info',
];

$commands['cd'] = [
  'name' => 'Change directory to connection mount directory',
  'aliases' => [
    'cd',
  ],
  'optional_args' => [
    'cid' => $params['arguments']['cid'],
  ],
  'cmd' => 'cmd_cd', 
];

$commands['ssh'] = [
  'name' => 'Launch SSH session',
  'aliases' => [
    'ssh',
  ],
  'optional_args' => [
    'cid' => $params['arguments']['cid'],
  ],
  'cmd' => 'cmd_ssh', 
];

// command functions

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function cmd_mount($args) {
  if (isset($args['cid'])) {
    $cid = $args['cid'];
  }
  else {
    $cid = choose_connection();
  }
  if (isset($args['password'])) {
    $password = $args['password'];
  }
  else {
    $password = FALSE;
  }

  $cmd = gen_mount_cmd($cid, $password);
  $connection_settings = get_connection_settings($cid);
  $success_message = '';
  if (isset($connection_settings['user'])) {
    $success_message .= $connection_settings['user'] . '@';
  }
  $success_message .= $connection_settings['server'] . ' ' . green('mounted') . ' to ' . $connection_settings['mount'] . PHP_EOL;
  echo run_cmd($cmd, $success_message);
  return;
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function cmd_unmount($args) {
  // @todo check that something is mounted
  if (isset($args['cid'])) {
    $cid = $args['cid'];
  }
  else {
    $cid = choose_connection(TRUE);
  }

  $cmd = gen_unmount_cmd($cid);
  $connection_settings = get_connection_settings($cid);
  $success_message = '';
  if (isset($connection_settings['user'])) {
    $success_message .= $connection_settings['user'] . '@';
  }
  $success_message .= $connection_settings['server'] . ' ' . green('unmounted') . PHP_EOL;
  echo run_cmd($cmd, $success_message);
  return;
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function cmd_add($args) {
  global $global;
  $connection_settings = [];
  $connection_settings['server'] = read_input('Server (required): ', NULL, TRUE);
  $connection_settings['port'] = read_input('Port (default "22"): ');
  $connection_settings['user'] = read_input('Username: ');
  $connection_settings['password'] = read_input('Password (Input hidden. If password not provided, it will be asked every time on connect. Leave blank for key auth): ', NULL, FALSE, TRUE);
  $connection_settings['key'] = read_input('Path to key file (Usually "~/.ssh/id_rsa". Leave blank for password auth): ');

  $default_mount = '~/mnt/' . $connection_settings['server'];
  $connection_settings['mount'] = read_input('Mount directory (Required for mounting. [Enter] - "' . $default_mount . '"): ', $default_mount);

  $connection_settings['remote'] = read_input('Remote directory: ');
 
  $options = readline('Mount options (separated by comma): ');
  $options = explode (',', $options);
  $options = array_map('trim', $options);
  $connection_settings['options'] = array_filter($options);

  $connection_settings['title'] = read_input('Connection name ([Enter] - "' . $connection_settings['server'] . '"): ', $connection_settings['server']);
  $cid = $connection_settings['title'];

  echo PHP_EOL;
  show_connection_settings($connection_settings);
  echo PHP_EOL;
  // @todo while loop
  $save_config = readline('Seve config (y, [Enter] - to user directory / c - to current directory / n - cancel): ');
  if (!$save_config || $save_config == 'y' || $save_config == 'Y' || $save_config == 'Yes' || $save_config == 'yes' || $save_config == 'YES') {
    $global = TRUE;
    return set_connection_settings($cid, $connection_settings);
  }
  elseif ($save_config == 'c' || $save_config == 'C') {
    $global = FALSE;
    return set_connection_settings($cid, $connection_settings, TRUE);
  }
  else {
    return;
  }
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function cmd_remove($args) {
  if (isset($args['cid'])) {
    $cid = $args['cid'];
  }
  else {
    $cid = choose_connection();
  }

  return remove_connection_settings($cid);
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function cmd_list($args) {
  if (isset($args['cid'])) {
    $cid = $args['cid'];
  }
  else {
    $cid = choose_connection();
  }

  $connection_settings = get_connection_settings($cid);
  show_connection_settings($connection_settings);
  return;
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function cmd_cd($args) {
  global $home;
  if (isset($args['cid'])) {
    $cid = $args['cid'];
  }
  else {
    $cid = choose_connection();
  }

  $connection_settings = get_connection_settings($cid);
  if (isset($connection_settings['mount'])) {
    if (substr($connection_settings['mount'], 0, 1) == '~') {
      $path = $home . substr($connection_settings['mount'], 1);
    }
    else {
      $path = $connection_settings['mount'];
    }
    $cd_cmd = 'cd ' . $path;
    run_terminal_cmd($cd_cmd);
    return;
  }
  else {
    echo 'No mountpoint for ' . $cid .  ' set' . PHP_EOL;
    exit(1);
  }
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function cmd_ssh($args) {
  if (isset($args['cid'])) {
    $cid = $args['cid'];
  }
  else {
    $cid = choose_connection();
  }

  $connection_settings = get_connection_settings($cid);

  $ssh_cmd = 'ssh ';
  if (isset($connection_settings['user'])) {
    $ssh_cmd .= $connection_settings['user'] . '@';
  }
  $ssh_cmd .= $connection_settings['server'];
  run_terminal_cmd($ssh_cmd);
  return;
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function cmd_status($args) {
  choose_connection(FALSE, TRUE);
  return;
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function cmd_config($args) {
  $config_file = get_config_file();
  $config_cmd = '$EDITOR ' . $config_file;
  shell_exec($config_cmd);
  return;
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function cmd_help($args) {
  if (isset($args['cmd'])) {
    $cmd = $args['cmd'];
  }
  else {
    $cmd = 'default';
  }
  // @todo
  echo '<Show help> cmd: ' . $cmd . PHP_EOL;
  return;
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function cmd_version($args) {
  global $project_info_file;
  $project_info = file_get_contents($project_info_file);
  $project_info = json_decode($project_info, true);
  echo $project_info['version'] . PHP_EOL;
  return;
}

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function cmd_info($args) {
  global $project_info_file;
  $info = [];
  $project_info = file_get_contents($project_info_file);
  $project_info = json_decode($project_info, true);
  $info[] = 'SSHFS Mount Tool v' . $project_info['version'];
  exec('sshfs --version 2> /dev/null', $info);
  // @todo check for other dependencies
  // @todo show as table "dependency version : status"
  foreach ($info as $key => $line) {
    echo $line . PHP_EOL;
  }
  return;
}

// handle input

/**
 * Description of function.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function is_global($argv) {
  global $global;
  foreach ($argv as $arg_key => $arg_value) {
    if ($arg_value == '--global' || $arg_value == '-g') {
      $global = TRUE;
    }
  }
  return;
}

/**
 * Parse script args.
 *
 * @param $var1
 *  Description of $var1.
 *
 * @return
 *  Description of return.
 */
function resolve_args($argv, $argc) {
  global $commands;
  global $params;
  $args = [];

  if ($argc == 1) {
    // no args
    $cmd_cmd = $commands['default']['cmd'];
  }
  else {
    // has args

    // remove first arg - script name
    array_shift($argv);  

    // check new first arg is cmd
    $cmd = match_cmd($argv[0]);
    if ($cmd) {
      $cmd_cmd = $commands[$cmd]['cmd'];
      // command found, remove it from args
      array_shift($argv);
    }
    else {
      $cmd_cmd = $commands['default']['cmd'];
    }

    if (!empty($argv)) {
      // here left only options

      $skip_next_arg = FALSE;
      foreach ($argv as $arg_key => $arg_value) {
        // skip iterration if argument already used (for flag value)
        if (!$skip_next_arg) {
          if (substr($arg_value, 0, 1) != '-') {
            // looks like an argument

            $arg_found = FALSE;
            // check for arguments
            foreach ($params['arguments'] as $parg_key => $parg_value) {
              // check for validation
              if (isset($parg_value['validate'])) {
                $arg_validate = $parg_value['validate'];
                $arg_valid = $arg_validate($arg_value);
                if ($arg_valid) {
                  // check arg already exist
                  if (!isset($args[$parg_key])) {
                    $args[$parg_key] = $arg_valid;
                    $arg_found = TRUE;
                    break;
                  }
                }
              }
            }

            if (!$arg_found) {
              // arg not mach any validations
              echo 'Unknown command or argument ' . $arg_value . PHP_EOL;
              return;
            }

          }
          else {
            // looks like an option

            $arg_found = FALSE;
            // check for options
            foreach ($params['options'] as $popt_key => $popt_value) {
              // check for long or single short option
              if ($arg_value == '--' . $popt_key || isset($popt_value['key']) && $arg_value == '-' . $popt_value['key']) {
                // check arg already exist
                if (!isset($args[$popt_key])) {
                  $args[$popt_key] = TRUE;
                  $arg_found = TRUE;
                  break;
                }
              }

              // @todo check for multiple short options
              // possible options (-vsyg)
            }

            // check for flags
            foreach ($params['flags'] as $pflg_key => $pflg_value) {
              // check for long or short flag
              if ($arg_value == '--' . $pflg_key || $arg_value == '-' . $pflg_value['key']) {
                // check arg already exist
                if (!isset($args[$pflg_key])) {
                  if (isset($argv[$arg_key + 1])) {
                    $args[$pflg_key] = $argv[$arg_key + 1];
                    $skip_next_arg = TRUE;
                    $arg_found = TRUE;
                    break;
                  }
                }
              }

              // @todo check for value goes right after key without space
              // possible flag (-psomepass)

              // @todo check for value goes right after key with equal symbol
              // possible flag (-p=somepass)
            }

            if (!$arg_found) {
              // arg not mach any options
              echo 'Unknown option ' . $arg_value . PHP_EOL;
              return;
            }
            
          }
        }
        else {
          // reset skip
          $skip_next_arg = FALSE;
        }
      }
    }
  }

  // fallback for wrong command order
  if ($cmd_cmd == $commands['default']['cmd'] && isset($args['cmd'])) {
    $cmd = $args['cmd'];
    $cmd_cmd = $commands[$cmd]['cmd'];
    unset($args['cmd']);
  }

  // fallback for wrong command order for help
  if ($cmd_cmd != $commands['help']['cmd'] && isset($args['cmd']) && $args['cmd'] == 'help' ||
      $cmd_cmd != $commands['help']['cmd'] && isset($args['help'])) {
    foreach ($commands as $cmd => $command_settings) {
      if ($cmd_cmd == $command_settings['cmd']) {
        $args['cmd'] = $cmd;
        $cmd_cmd = $commands['help']['cmd'];
        break;
      }
    }
  }

  // check for double command
  if (isset($args['cmd']) && $cmd_cmd == $commands[$args['cmd']]['cmd']) {
    echo 'Unexpected argument for ' . $args['cmd'] . PHP_EOL;
    return;
  }

  // no matter what haapened before, action should be always only this
  return $cmd_cmd($args);
}

// Main function

is_global($argv);
resolve_args($argv, $argc);
