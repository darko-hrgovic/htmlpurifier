<?php

require_once 'HTMLPurifier/AttrDef.php';
require_once 'HTMLPurifier/CSSDefinition.php';

class HTMLPurifier_AttrDef_CSS
{
    
    function validate($css, $config, &$context) {
        
        $definition = HTMLPurifier_CSSDefinition::instance();
        
        // we're going to break the spec and explode by semicolons.
        // This is because semicolon rarely appears in escaped form
        
        $declarations = explode(';', $css);
        $propvalues = array();
        
        foreach ($declarations as $declaration) {
            if (!$declaration) continue;
            if (!strpos($declaration, ':')) continue;
            list($property, $value) = explode(':', $declaration, 2);
            if (!isset($definition->info[$property])) continue;
            $result = $definition->info[$property]->validate($value,$config,$context);
            if ($result === false) continue;
            $propvalues[$property] = $result;
        }
        
        $new_declarations = '';
        foreach ($propvalues as $prop => $value) {
            $new_declarations .= "$prop:$value;";
        }
        
        return $new_declarations ? $new_declarations : false;
        
    }
    
}

?>