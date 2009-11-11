<?php

class sfJSLibManager {

  private static $libs_loaded = array();

  public static function getStylesheets($lib_name, array $lib_config = null)
  {
    return sfJSLibManager::getLibAssets($lib_name, $lib_config, 'css');
  }

  public static function getJavascripts($lib_name, array $lib_config = null)
  {
    return sfJSLibManager::getLibAssets($lib_name, $lib_config, 'js');
  }

  private static function getLibAssets($lib_name, array $lib_config = null, $asset_type)
  {
    $assets = array();

    if (is_null($lib_config))
    {
      $lib_config = sfConfig::get('sf_js_lib_' . $lib_name, false);
    }

    if ($lib_config)
    {
      if (!empty($lib_config['dependencies']))
      {
        $assets = sfJSLibManager::getDependencies($lib_config['dependencies'], $asset_type);
      }

      $assets = array_merge($assets, sfJSLibManager::getAssets($lib_config, $asset_type));
    }

    return $assets;
  }

  /**
   *
   * @param string $lib_name
   * @param array $lib_config
   * @return boolean success or failure
   *
   * First tries to load an dependency lib's asset files before trying to load
   * any javascript & css asset files as specified in the library's settings
   *
   */
  public static function addLib($lib_name, array $lib_config = null)
  {
    if (is_null($lib_config))
    {
      $lib_config = sfConfig::get('sf_js_lib_' . $lib_name, false);
    }

    /**
     * Return if we don't find any settings for the library
     */

    if (!$lib_config)
    {
      return false;
    }

    if (sfJSLibManager::isLoaded($lib_name, $lib_config))
    {
      return true;
    }

    if (!empty($lib_config['dependencies']))
    {
      if (!sfJSLibManager::addDependencies($lib_config['dependencies']))
      {
        return false;
      }
    }

    if (!sfJSLibManager::addAssets($lib_config))
    {
      return false;
    }

    sfJSLibManager::addLoaded($lib_name, $lib_config);

    return true;
  }

  private static function libToString($lib_name, $lib_config)
  {
    return $lib_name . '_' . serialize($lib_config);
  }

  /**
   *
   * @param string $loaded_name
   * @return boolean true if the
   */
  public static function isLoaded($lib_name, $lib_config)
  {
    $lib_string = sfJSLibManager::libToString($lib_name, $lib_config);

    return in_array($lib_string, self::$libs_loaded);
  }

  private static function addLoaded($lib_name, $lib_config)
  {
    $lib_string = sfJSLibManager::libToString($lib_name, $lib_config);

    return self::$libs_loaded[] = $lib_string;
  }

  /**
   *
   * @param array $lib_config
   *
   * Load any assets specified in the assoc $lib_config array
   * (type => loadMethod)
   *
   */
  private static function getAssets($lib_config, $asset_type)
  {
    $assets = array();

    $setting = $asset_type . '_files';

    if (!empty($lib_config[$setting]))
    {
      if (!is_array($lib_config[$setting]))
      {
        $lib_config[$setting] = array($lib_config[$setting]);
      }

      foreach ($lib_config[$setting] as $asset_file)
      {
        $assets[] = $lib_config['web_dir'] . '/' . $asset_type . '/' . $asset_file;
      }
    }

    return $assets;
  }

  /**
   *
   * @param array $lib_config
   *
   * Load any assets specified in the assoc $lib_config array
   * (type => loadMethod)
   *
   */
  private static function addAssets($lib_config)
  {
    $response = sfContext::getInstance()->getResponse();

    /**
     * Loop through the specified javascript & css files and add them to the
     * response
     */

    $asset_types = array(
      'js' => 'addJavascript',
      'css' => 'addStyleSheet',
    );

    foreach ($asset_types as $asset_type => $method)
    {
      $setting = $asset_type . '_files';

      if (empty($lib_config[$setting]))
      {
        continue;
      }

      if (!is_array($lib_config[$setting]))
      {
        $lib_config[$setting] = array($lib_config[$setting]);
      }

      foreach ($lib_config[$setting] as $asset_file)
      {
        $response->$method($lib_config['web_dir'] . '/' . $asset_type . '/' . $asset_file);
      }
    }

    return true;
  }

  /**
   *
   * @param mixed $dependencies
   * @return array
   */
  private static function getDependencies($dependencies, $asset_type)
  {
    $assets = array();

    if (!is_array($dependencies))
    {
      $dependencies = array($dependencies);
    }

    foreach ($dependencies as $dependency)
    {
      $assets = array_merge($assets, sfJSLibManager::getStylesheets($dependency, 'css'));
    }

    return $assets;
  }

  /**
   *
   * @param mixed $dependencies
   * @return boolean success or failure
   */
  private static function addDependencies($dependencies)
  {

    if (!is_array($dependencies))
    {
      $dependencies = array($dependencies);
    }

    foreach ($dependencies as $dependency)
    {
      if (!sfJSLibManager::addLib($dependency))
      {
        return false;
      }
    }

    return true;
  }
}