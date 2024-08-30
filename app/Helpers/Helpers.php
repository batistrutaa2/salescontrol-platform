<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Helpers
{
  public static function appClasses()
  {

    $data = config('custom.custom');


    // default data array
    $DefaultData = [
      'myLayout' => 'vertical',
      'myTheme' => 'theme-default',
      'myStyle' => 'light',
      'myRTLSupport' => true,
      'myRTLMode' => true,
      'hasCustomizer' => true,
      'showDropdownOnHover' => true,
      'displayCustomizer' => true,
      'contentLayout' => 'compact',
      'headerType' => 'fixed',
      'navbarType' => 'fixed',
      'menuFixed' => true,
      'menuCollapsed' => false,
      'footerFixed' => false,
      'menuFlipped' => false,
      // 'menuOffcanvas' => false,
      'customizerControls' => [
        'rtl',
        'style',
        'headerType',
        'contentLayout',
        'layoutCollapsed',
        'showDropdownOnHover',
        'layoutNavbarOptions',
        'themes',
      ],
      //   'defaultLanguage'=>'en',
    ];

    // if any key missing of array from custom.php file it will be merge and set a default value from dataDefault array and store in data variable
    $data = array_merge($DefaultData, $data);

    // All options available in the template
    $allOptions = [
      'myLayout' => ['vertical', 'horizontal', 'blank', 'front'],
      'menuCollapsed' => [true, false],
      'hasCustomizer' => [true, false],
      'showDropdownOnHover' => [true, false],
      'displayCustomizer' => [true, false],
      'contentLayout' => ['compact', 'wide'],
      'headerType' => ['fixed', 'static'],
      'navbarType' => ['fixed', 'static', 'hidden'],
      'myStyle' => ['light', 'dark', 'system'],
      'myTheme' => ['theme-default', 'theme-bordered', 'theme-semi-dark'],
      'myRTLSupport' => [true, false],
      'myRTLMode' => [true, false],
      'menuFixed' => [true, false],
      'footerFixed' => [true, false],
      'menuFlipped' => [true, false],
      // 'menuOffcanvas' => [true, false],
      'customizerControls' => [],
      // 'defaultLanguage'=>array('en'=>'en','fr'=>'fr','de'=>'de','ar'=>'ar'),
    ];

    //if myLayout value empty or not match with default options in custom.php config file then set a default value
    foreach ($allOptions as $key => $value) {
      if (array_key_exists($key, $DefaultData)) {
        if (gettype($DefaultData[$key]) === gettype($data[$key])) {
          // data key should be string
          if (is_string($data[$key])) {
            // data key should not be empty
            if (isset($data[$key]) && $data[$key] !== null) {
              // data key should not be exist inside allOptions array's sub array
              if (!array_key_exists($data[$key], $value)) {
                // ensure that passed value should be match with any of allOptions array value
                $result = array_search($data[$key], $value, 'strict');
                if (empty($result) && $result !== 0) {
                  $data[$key] = $DefaultData[$key];
                }
              }
            } else {
              // if data key not set or
              $data[$key] = $DefaultData[$key];
            }
          }
        } else {
          $data[$key] = $DefaultData[$key];
        }
      }
    }
    $styleVal = $data['myStyle'] == "dark" ? "dark" : "light";
    $styleUpdatedVal = $styleVal;
    if (isset($_COOKIE['mode'])) {
      if ($_COOKIE['mode'] === "system") {
        if (isset($_COOKIE['colorPref'])) {
          $styleVal = Str::lower($_COOKIE['colorPref']);
        }
      } else {
        $styleVal = $_COOKIE['mode'];
      }
      $styleUpdatedVal = $_COOKIE['mode'];
    }
    isset($_COOKIE['theme']) ? $themeVal = $_COOKIE['theme'] : $themeVal = $data['myTheme'];

    $directionVal = isset($_COOKIE['direction']) ? ($_COOKIE['direction'] === "true" ? 'rtl' : 'ltr') : $data['myRTLMode'];

    //layout classes
    $layoutClasses = [
      'layout' => $data['myLayout'],
      'theme' => $themeVal,
      'themeOpt' => $data['myTheme'],
      'style' => $styleVal,
      'styleOpt' => $data['myStyle'],
      'styleOptVal' => $styleUpdatedVal,
      'rtlSupport' => $data['myRTLSupport'],
      'rtlMode' => $data['myRTLMode'],
      'textDirection' => $directionVal, //$data['myRTLMode'],
      'menuCollapsed' => $data['menuCollapsed'],
      'hasCustomizer' => $data['hasCustomizer'],
      'showDropdownOnHover' => $data['showDropdownOnHover'],
      'displayCustomizer' => $data['displayCustomizer'],
      'contentLayout' => $data['contentLayout'],
      'headerType' => $data['headerType'],
      'navbarType' => $data['navbarType'],
      'menuFixed' => $data['menuFixed'],
      'footerFixed' => $data['footerFixed'],
      'menuFlipped' => $data['menuFlipped'],
      'customizerControls' => $data['customizerControls'],
    ];

    // sidebar Collapsed
    if ($layoutClasses['menuCollapsed'] == true) {
      $layoutClasses['menuCollapsed'] = 'layout-menu-collapsed';
    }

    // Header Type
    if ($layoutClasses['headerType'] == 'fixed') {
      $layoutClasses['headerType'] = 'layout-menu-fixed';
    }
    // Navbar Type
    if ($layoutClasses['navbarType'] == 'fixed') {
      $layoutClasses['navbarType'] = 'layout-navbar-fixed';
    } elseif ($layoutClasses['navbarType'] == 'static') {
      $layoutClasses['navbarType'] = '';
    } else {
      $layoutClasses['navbarType'] = 'layout-navbar-hidden';
    }

    // Menu Fixed
    if ($layoutClasses['menuFixed'] == true) {
      $layoutClasses['menuFixed'] = 'layout-menu-fixed';
    }


    // Footer Fixed
    if ($layoutClasses['footerFixed'] == true) {
      $layoutClasses['footerFixed'] = 'layout-footer-fixed';
    }

    // Menu Flipped
    if ($layoutClasses['menuFlipped'] == true) {
      $layoutClasses['menuFlipped'] = 'layout-menu-flipped';
    }

    // Menu Offcanvas
    // if ($layoutClasses['menuOffcanvas'] == true) {
    //   $layoutClasses['menuOffcanvas'] = 'layout-menu-offcanvas';
    // }

    // RTL Supported template
    if ($layoutClasses['rtlSupport'] == true) {
      $layoutClasses['rtlSupport'] = '/rtl';
    }

    // RTL Layout/Mode
    if ($layoutClasses['rtlMode'] == true) {
      $layoutClasses['rtlMode'] = 'rtl';
      $layoutClasses['textDirection'] = isset($_COOKIE['direction']) ? ($_COOKIE['direction'] === "true" ? 'rtl' : 'ltr') : 'rtl';
    } else {
      $layoutClasses['rtlMode'] = 'ltr';
      $layoutClasses['textDirection'] = isset($_COOKIE['direction']) && $_COOKIE['direction'] === "true" ? 'rtl' : 'ltr';
    }

    // Show DropdownOnHover for Horizontal Menu
    if ($layoutClasses['showDropdownOnHover'] == true) {
      $layoutClasses['showDropdownOnHover'] = true;
    } else {
      $layoutClasses['showDropdownOnHover'] = false;
    }

    // To hide/show display customizer UI, not js
    if ($layoutClasses['displayCustomizer'] == true) {
      $layoutClasses['displayCustomizer'] = true;
    } else {
      $layoutClasses['displayCustomizer'] = false;
    }

    return $layoutClasses;
  }

  public static function updatePageConfig($pageConfigs)
  {
    $demo = 'custom';
    if (isset($pageConfigs)) {
      if (count($pageConfigs) > 0) {
        foreach ($pageConfigs as $config => $val) {
          Config::set('custom.' . $demo . '.' . $config, $val);
        }
      }
    }
  }

  public static function cleanSpecialCharacters($string)
  {
    $string = preg_replace('/[^A-Za-z0-9]/', '', $string);
    return $string;
  }

  public static function generateUniqueId()
  {
    // Obter o timestamp atual
    $timestamp = Carbon::now()->timestamp;

    // Gerar uma pequena string aleatória para garantir unicidade
    $randomString = substr(md5(uniqid(rand(), true)), 0, 5);

    // Combinar o timestamp com a string aleatória
    return $timestamp . $randomString;
  }

  public static function normalizeStatusName($string)
  {
    // Convertendo para minúsculas
    $string = strtolower($string);

    // Substituindo caracteres especiais
    $search = ['á', 'é', 'í', 'ó', 'ú', 'ç', 'ã', 'õ', 'â', 'ê', 'î', 'ô', 'û', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ç', 'Ã', 'Õ', 'Â', 'Ê', 'Î', 'Ô', 'Û'];
    $replace = ['a', 'e', 'i', 'o', 'u', 'c', 'a', 'o', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'c', 'a', 'o', 'a', 'e', 'i', 'o', 'u'];
    $string = str_replace($search, $replace, $string);

    // Substituindo espaços e outros caracteres indesejados por hífens
    $string = preg_replace('/[^a-z0-9]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);

    return trim($string, '-');
  }

  /**
   * @param $value
   * @return string
   */
  public static function moneyForRealSaveBank($value): string
  {
    $value = str_replace('.', '', $value);
    $value = str_replace('$', '', $value);
    $value = str_replace('R', '', $value);
    $value = str_replace(' ', '', $value);
    return str_replace(',', '.', $value);
  }


  public static function formatCurrencyToDecimal($value)
  {
    // Remove o símbolo de R$ e qualquer outro caractere não numérico, exceto o ponto e a vírgula
    $cleanedValue = preg_replace('/[^0-9,]/', '', $value);

    // Substitui a vírgula por ponto, se necessário
    $decimalValue = str_replace(',', '.', $cleanedValue);

    // Converte para float ou decimal
    return (float) $decimalValue;
  }


  /**
   * Converte uma string de valor monetário para um número decimal.
   *
   * @param string|null $valor O valor em formato monetário.
   * @return float O valor convertido para decimal.
   */
  public static function converterParaDecimal($valor)
  {

    if (is_null($valor) || trim($valor) === '' || $valor === 'R$ 0') {
      return 0;
    }

    $valor = str_replace(['R$', '.', ','], ['', '', '.'], $valor);

    if (!is_numeric($valor)) {
      return 0;
    }

    $valorDecimal = number_format((float)$valor, 2, '.', '');

    return $valorDecimal;
  }


  public static function excelDateToPhpDate($serialDate)
  {
    if (is_null($serialDate) || $serialDate === '') {
      return $serialDate;
    }

    if (!is_numeric($serialDate)) {
      return $serialDate;
    }
    $serialDate = floatval($serialDate);

    try {
      $unixDate = ($serialDate - 25569) * 86400;
      return date('Y-m-d', $unixDate);
    } catch (\Exception $e) {
      return $serialDate;
    }
  }
}
