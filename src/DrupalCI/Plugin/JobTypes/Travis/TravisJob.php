<?php
/**
 * @file
 * Job class for PrivateTravis jobs on DrupalCI.
 */

namespace DrupalCI\Plugin\JobTypes\Travis;

use DrupalCI\Plugin\JobTypes\JobBase;
use DrupalCI\Console\Jobs\Definition\JobDefinition;
use PrivateTravis\Permutation;

/**
 * @PluginID("travis")
 */

class TravisJob extends JobBase {

  // TODO: Currently legacy leftover code.  TBD whether this class gets carried forward as an independent job type.

  protected $namespace = "privatetravis";

  public $jobtype = "travis";

  public function set_namespace($namespace) {
    $this->namespace = $namespace;
  }

  public function get_namespace() {
    return $this->namespace;
  }

  protected $travisfile = ".travis.yml";

  public function set_travisfile($travisfile) {
    $this->travisfile = $travisfile;
  }

  public function get_travisfile() {
    return $this->travisfile;
  }

  protected $commands = array(
    'env',
    'before_script',
    'script',
  );

  public function set_commands($commands) {
    $this->commands = $commands;
  }

  public function get_commands($commands) {
    return $this->commands;
  }

  // Placeholder to store the parsed .travisci.yml file contents
  protected $travis_parsed = NULL;

  // Placeholder to store the auto-generated bootstrap script
  protected $script = "";

  public $allowed_arguments = array(
    'DCI_TravisFile',
    'DCI_Namespace',
    'DCI_TravisCommands',
    'DCI_FastFail',
    'DCI_Privileged',
  );

  public $defaultArguments = array(
    'DCI_TravisFile' => '.travis.yml',
  );

  public $requiredArguments = array(
    'DCI_TravisFile' => 'build_vars:travis_filename',
  );

  public function buildSteps() {
    return array(
      'validate',
        //'checkout',
        //'environment',
      'setup',
      //'install',
      //'validate_install',
        //'execute',
      //'complete',
      //'success',
      //'failure'
    );
  }

  public function environment() {

    // Load and parse travis file
    $travis_file = $this->buildVars['DCI_TravisFile'];
    $this->output->writeln("<comment>Loading test build parameters from travis file: </comment><info>$travis_file</info>");
    $build = new JobDefinition();
    $directory = trim($this->workingDirectory);
    // Ensure directory ends in a /
    if ($directory[strlen($directory) -1] != '/') {
      $directory = $directory . "/";
    }
    $result = $build->load($directory . $travis_file);
    if ($result == -1) {
      // Error loading definition file.
      $this->output->writeln("<error>FAILED:</error> <info>Unable to parse travis file.</info>");
      // TODO: Robust error handling
      return -1;
    };
    $travis = $build->getParameters();

    // Store the parsed contents so we can reference them in later build steps
    $this->travis_parsed = $travis;

    $namespace = $this->namespace;
    $command = implode(' ', $this->commands);

    // Evaluate the parsed travis file
    $language = !empty($travis['language']) ? $travis['language'] : '';
    $language_versions = !empty($travis[$language]) ? $travis[$language] : array();
    $services = !empty($travis['services']) ? $travis['services'] : array();

    // TODO: Add Fast Fail logic

    // Get the permutations
    foreach ($language_versions as $language_version) {
      $this->output->writeln("<info>### Building permutation <options=bold>'$language$language_version'</options=bold> ###</info>");

      $permutation = new Permutation();
      $permutation->setNamespace($namespace);
      $permutation->setLanguage($language . ':' . $language_version);
      $permutation->setCommand($command);
      if (!empty($this->buildVars['DCI_Privileged'])) {
        $permutation->setPrivileged(true);
      }
      $permutation->addServices($services);

      // Print.
      $lines = $permutation->build();
      foreach ($lines as $line) {
        $this->output->writeln($line);
        if (!empty($this->script)) {
          $this->script .= " && ";
        }
        $this->script .= $line;
      }
    }
  }

  public function execute() {
    // Execute Script
    $this->output->writeln("<comment>Starting Travis Job execution.</comment>");
    //echo "Script: " . print_r($this->script, TRUE);
    //foreach ($this->script as $cmd) {
    $dir = trim($this->workingDirectory);
    $cmd = "cd $dir && " . $this->script;
    echo "Script Cmd: " . $cmd;
    $this->shellCommand($cmd);
    //}
  }

}
