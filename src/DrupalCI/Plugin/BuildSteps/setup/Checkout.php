<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\setup\Checkout
 *
 * Processes "setup: checkout:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\BuildSteps\setup;

use DrupalCI\Console\Output;
use DrupalCI\Plugin\JobTypes\JobInterface;

/**
 * @PluginID("checkout")
 */
class Checkout extends SetupBase {

  /**
   * {@inheritdoc}
   */
  public function run(JobInterface $job, $data) {
    // Data format:
    // i) array('protocol' => 'local', 'srcdir' => '/tmp/drupal', ['checkout_dir' => '/tmp/checkout'])
    // or
    // ii) array('protocol' => 'git', 'repo' => 'git://code.drupal.org/drupal.git', 'branch' => '8.0.x', ['depth' => 1])
    // or
    // iii) array(array(...), array(...))
    // Normalize data to the third format, if necessary
    $data = (count($data) == count($data, COUNT_RECURSIVE)) ? [$data] : $data;

    Output::writeLn("<info>Entering setup_checkout().</info>");
    foreach ($data as $key => $details ) {
      // TODO: Ensure $details contains all required parameters
      $protocol = isset($details['protocol']) ? $details['protocol'] : 'git';
      $func = 'setupCheckout' . ucfirst($protocol);
      $this->$func($job, $details);
      if ($job->getErrorState()) {
        break;
      }
    }
    return;
  }

  protected function setupCheckoutLocal(JobInterface $job, $details) {
    Output::writeLn("<info>Entering setupCheckoutLocal().</info>");
    $srcdir = isset($details['srcdir']) ? $details['srcdir'] : './';
    $workingdir = $job->getWorkingDir();
    $checkoutdir = isset($details['checkout_dir']) ? $details['checkout_dir'] : $workingdir;
    // TODO: Ensure we don't end up with double slashes
    // Validate source directory
    $source = realpath($srcdir);
    if (empty($source)) {
      $job->errorOutput("Error", "The source directory <info>$srcdir</info> does not exist.");
      return;
    }
    // Validate target directory.  Must be within workingdir.
    if (!($directory = $this->validateDirectory($job, $checkoutdir))) {
      // Invalidate checkout directory
      $job->errorOutput("Error", "The checkout directory <info>$directory</info> is invalid.");
      return;
    }
    Output::writeln("<comment>Copying files from <options=bold>$srcdir</options=bold> to the local checkout directory <options=bold>$directory</options=bold> ... </comment>");
    exec("cp -r $srcdir/* $directory", $cmdoutput, $result);
    if (is_null($result)) {
      $job->errorOutput("Failed", "Error encountered while attempting to copy code to the local checkout directory.");
      return;
    }
    Output::writeLn("<comment>DONE</comment>");
  }

  protected function setupCheckoutGit(JobInterface $job, $details) {
    Output::writeLn("<info>Entering setup_checkout_git().</info>");
    $repo = isset($details['repo']) ? $details['repo'] : 'git://drupalcode.org/project/drupal.git';
    $gitbranch = isset($details['branch']) ? $details['branch'] : 'master';
    $gitdepth = isset($details['depth']) ? $details['depth'] : NULL;
    $workingdir = $job->getWorkingDir();

    $checkoutdir = isset($details['checkout_dir']) ? $details['checkout_dir'] : $workingdir;
    // TODO: Ensure we don't end up with double slashes
    // Validate target directory.  Must be within workingdir.
    if (!($directory = $this->validateDirectory($job, $checkoutdir))) {
      // Invalid checkout directory
      $job->errorOutput("Error", "The checkout directory <info>$directory</info> is invalid.");
      return;
    }
    Output::writeLn("<comment>Performing git checkout of $repo $gitbranch branch to $directory.</comment>");

    $cmd = "git clone -b $gitbranch $repo $directory";
    if (!is_null($gitdepth)) {
      $cmd .=" --depth=$gitdepth";
    }
    exec($cmd, $cmdoutput, $result);
    if ($result !==0) {
      // Git threw an error.
      $job->errorOutput("Checkout failed", "The git checkout returned an error.");
      // TODO: Pass on the actual return value for the git checkout
      return;
    }
    Output::writeLn("<comment>Checkout complete.</comment>");
  }

}
