<?php

// MF = Markup Fragment
// all objects here are immutable

class MF {}

class MF_Tag extends MF
{
    var $name;
    function MF_Tag($name) {
        $this->name = strtolower($name); // for some reason, the SAX parser
                                         // uses uppercase. Investigate?
    }
}

class MF_TagWithAttributes extends MF_Tag
{
    var $attributes = array();
    function MF_TagWithAttributes($name, $attributes = array()) {
        $this->MF_Tag($name);
        $this->attributes = $attributes;
    }
}

class MF_StartTag extends MF_TagWithAttributes {}
class MF_EmptyTag extends MF_TagWithAttributes {}
class MF_EndTag extends MF_Tag {}

class MF_Text extends MF
{
    var $name = '#PCDATA';
    var $data;
    var $is_whitespace = false;
    function MF_Text($data) {
        $this->data = $data;
        if (trim($data, " \n\r\t") === '') $this->is_whitespace = true;
    }
    function append($mf_text) {
        return new MF_Text($this->data . $mf_text->data);
    }
}

class MF_Comment extends MF
{
    var $data;
    function MF_Comment($data) {
        $this->data = $data;
    }
}

?>