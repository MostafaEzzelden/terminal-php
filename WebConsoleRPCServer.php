<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseJsonRpcServer;

// RPC Server
class WebConsoleRPCServer extends BaseJsonRpcServer
{

    // Authentication
    private function authenticateUser($user, $password)
    {
        $user = trim((string) $user);
        $password = trim((string) $password);

        if ($user && $password) {

            $accounts = config('terminal.accounts');

            if (isset($accounts[$user]) && !empty($accounts[$user])) {
                $algorithm = config('terminal.passwordHashAlgorithm');

                if ($algorithm) {
                    $password = hash($algorithm, trim((string) $password));
                }

                if (strcmp($password, $accounts[$user]) == 0) {
                    return $user . ':' . hash('sha256', $password);
                }
            }
        }

        throw new \Exception("Incorrect user or password");
    }

    private function authenticateToken($token)
    {
        if (config('terminal.noLogin')) {
            return true;
        }

        $token = trim((string) $token);
        $token_parts = explode(':', $token, 2);

        if (count($token_parts) == 2) {
            $user = trim((string) $token_parts[0]);
            $password_hash = trim((string) $token_parts[1]);

            if ($user && $password_hash) {
                if (isset(config('terminal.accounts')[$user]) && !empty(config('terminal.accounts')[$user])) {
                    $real_password_hash = hash('sha256', config('terminal.accounts')[$user]);
                    if (strcmp($password_hash, $real_password_hash) == 0) return $user;
                }
            }
        }

        throw new \Exception("Incorrect user or password");
    }

    private function getHomeDirectory($user)
    {
        $homeDirectory = config('terminal.homeDirectory');

        if (is_string($homeDirectory)) {
            if (!empty($homeDirectory)) {
                return $homeDirectory;
            }
        } else if (is_string($user) && !empty($user) && isset($homeDirectory[$user]) && !empty($homeDirectory[$user]))
            return $homeDirectory[$user];

        return getcwd();
    }

    // Environment
    private function getEnvironment()
    {
        $hostname = function_exists('gethostname') ? gethostname() : null;
        return array('path' => getcwd(), 'hostname' => $hostname);
    }

    private function setEnvironment($environment)
    {
        $environment = !empty($environment) ? (array) $environment : array();
        $path = (isset($environment['path']) && !empty($environment['path'])) ? $environment['path'] : $this->home_directory;

        if (!empty($path)) {
            if (is_dir($path)) {
                if (!@chdir($path)) return array(
                    'output' => "Unable to change directory to current working directory, updating current directory",
                    'environment' => $this->getEnvironment()
                );
            } else return array(
                'output' => "Current working directory not found, updating current directory",
                'environment' => $this->getEnvironment()
            );
        }
    }

    // Initialization
    private function initialize($token, $environment)
    {
        $user = $this->authenticateToken($token);
        $this->home_directory = $this->getHomeDirectory($user);
        $result = $this->setEnvironment($environment);

        if ($result) return $result;
    }

    // Methods
    public function login($user, $password)
    {
        $result = array(
            'token' => $this->authenticateUser($user, $password),
            'environment' => $this->getEnvironment()
        );

        $homeDirectory = $this->getHomeDirectory($user);
        if (!empty($homeDirectory)) {
            if (is_dir($homeDirectory)) $result['environment']['path'] = $homeDirectory;
            else $result['output'] = "Home directory not found: " . $homeDirectory;
        }

        return $result;
    }

    public function cd($token, $environment, $path)
    {
        $result = $this->initialize($token, $environment);
        if ($result) return $result;

        $path = trim((string) $path);
        if (empty($path)) $path = $this->home_directory;

        if (!empty($path)) {
            if (is_dir($path)) {
                if (!@chdir($path)) return array('output' => "cd: " . $path . ": Unable to change directory");
            } else return array('output' => "cd: " . $path . ": No such directory");
        }

        return array('environment' => $this->getEnvironment());
    }

    public function completion($token, $environment, $pattern, $command)
    {
        $result = $this->initialize($token, $environment);
        if ($result) return $result;

        $scan_path = '';
        $completion_prefix = '';
        $completion = array();

        if (!empty($pattern)) {
            if (!is_dir($pattern)) {
                $pattern = dirname($pattern);
                if ($pattern == '.') $pattern = '';
            }

            if (!empty($pattern)) {
                if (is_dir($pattern)) {
                    $scan_path = $completion_prefix = $pattern;
                    if (substr($completion_prefix, -1) != '/') $completion_prefix .= '/';
                }
            } else $scan_path = getcwd();
        } else {
            $scan_path = getcwd();
        }

        if (!empty($scan_path)) {
            // Loading directory listing
            $completion = array_values(array_diff(scandir($scan_path), array('..', '.')));
            natsort($completion);

            // Prefix
            if (!empty($completion_prefix) && !empty($completion)) {
                foreach ($completion as &$value) $value = $completion_prefix . $value;
            }

            // Pattern
            if (!empty($pattern) && !empty($completion)) {
                $completion = array_values(array_filter($completion, function ($value) use ($pattern) {
                    return !strncmp($pattern, $value, strlen($pattern));
                }));
            }
        }

        return array('completion' => $completion);
    }

    public function run($token, $environment, $command)
    {
        $result = $this->initialize($token, $environment);
        if ($result) return $result;

        $output = ($command && !empty($command)) ? execute_command($command) : '';
        if ($output && substr($output, -1) == "\n") $output = substr($output, 0, -1);

        return array('output' => $output);
    }
}
