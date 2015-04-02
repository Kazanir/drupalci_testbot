<?php

/**
 * @file
 * Contains \DrupalCI\Plugin\PluginManager.
 */

namespace DrupalCI\Plugin;

use Drupal\Component\Annotation\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

class PluginManager {

  /**
   * @var array
   */
  protected $plugins;

  /**
   * @var string
   */
  protected $pluginType;

  public function __construct($plugin_type) {
    $this->pluginType = $plugin_type;
  }

  /**
   * Discovers the list of available plugins.
   */
  protected function discoverPlugins() {
    $dir = "src/DrupalCI/Plugin/$this->pluginType";
    $plugin_definitions = [];
    foreach (new \DirectoryIterator($dir) as $file) {
      if ($file->isDir() && !$file->isDot()) {
        $plugin_type = $file->getFilename();
        $plugin_namespaces = ["DrupalCI\\Plugin\\$this->pluginType\\$plugin_type" => ["$dir/$plugin_type"]];
        $discovery  = new AnnotatedClassDiscovery($plugin_namespaces, 'Drupal\Component\Annotation\PluginID');
        $plugin_definitions[$plugin_type] = $discovery->getDefinitions();
      }
    }
    return $plugin_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPlugin($type, $plugin_id) {
    if (!isset($this->pluginDefinitions)) {
      $this->pluginDefinitions = $this->discoverPlugins();
    }
    if (isset($this->pluginDefinitions[$type][$plugin_id])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin($type, $plugin_id, $configuration = []) {
    if (!$this->hasPlugin($type, $plugin_id)) {
      throw new PluginNotFoundException("Plugin type $type plugin id $plugin_id not found.");
    }
    if (!isset($this->plugins[$type][$plugin_id])) {
      if (isset($this->pluginDefinitions[$type][$plugin_id])) {
        $plugin_definition = $this->pluginDefinitions[$type][$plugin_id];
      }
      elseif (isset($this->pluginDefinitions['generic'][$plugin_id])) {
        $plugin_definition = $this->pluginDefinitions['generic'][$plugin_id];
      }
      else {
        throw new PluginNotFoundException("Plugin type $type plugin id $plugin_id not found.");
      }
      $this->plugins[$type][$plugin_id] = new $plugin_definition['class']($configuration, $plugin_id, $plugin_definition);
    }
    return $this->plugins[$type][$plugin_id];
  }
}
