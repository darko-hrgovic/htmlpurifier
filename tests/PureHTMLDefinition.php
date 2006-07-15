<?php

class Test_HTMLDTD_ChildDef extends UnitTestCase
{
    
    function assertSeries($inputs, $expect, $def) {
        foreach ($inputs as $i => $input) {
            $result = $def->validateChildren($input);
            if (is_bool($expect[$i])) {
                $this->assertIdentical($expect[$i], $result);
            } else {
                $this->assertEqual($expect[$i], $result);
                paintIf($result, $result != $expect[$i]);
            }
        }
    }
    
    function test_complex() {
        
        // the table definition
        $def = new HTMLDTD_ChildDef(
            '(caption?, (col*|colgroup*), thead?, tfoot?, (tbody+|tr+))');
        
        $inputs[0] = array();
        $expect[0] = false;
        
        // we really don't care what's inside, because if it turns out
        // this tr is illegal, we'll end up re-evaluating the parent node
        // anyway.
        $inputs[1] = array(
            new MF_StartTag('tr')       ,new MF_EndTag('tr')
            );
        $expect[1] = true;
        
        $inputs[2] = array(
            new MF_StartTag('caption')  ,new MF_EndTag('caption')
           ,new MF_StartTag('col')      ,new MF_EndTag('col')
           ,new MF_StartTag('thead')    ,new MF_EndTag('thead')
           ,new MF_StartTag('tfoot')    ,new MF_EndTag('tfoot')
           ,new MF_StartTag('tbody')    ,new MF_EndTag('tbody')
            );
        $expect[2] = true;
        
        $inputs[3] = array(
            new MF_StartTag('col')      ,new MF_EndTag('col')
           ,new MF_StartTag('col')      ,new MF_EndTag('col')
           ,new MF_StartTag('col')      ,new MF_EndTag('col')
           ,new MF_StartTag('tr')       ,new MF_EndTag('tr')
            );
        $expect[3] = true;
        
        $this->assertSeries($inputs, $expect, $def);
        
    }
    
    function test_simple() {
        
        // simple is actually an abstract class
        // but we're unit testing some of the conv. functions it gives
        
        $def = new HTMLDTD_ChildDef_Simple('foobar | bang |gizmo');
        $this->assertEqual($def->elements,
          array(
            'foobar' => true
           ,'bang'   => true
           ,'gizmo'  => true
          ));
        
        $def = new HTMLDTD_ChildDef_Simple(array('href', 'src'));
        $this->assertEqual($def->elements,
          array(
            'href' => true
           ,'src'  => true
          ));
    }
    
    function test_required_pcdata_forbidden() {
        
        $def = new HTMLDTD_ChildDef_Required('dt | dd');
        
        $inputs[0] = array();
        $expect[0] = false;
        
        $inputs[1] = array(
            new MF_StartTag('dt')
           ,new MF_Text('Term')
           ,new MF_EndTag('dt')
           
           ,new MF_Text('Text in an illegal location')
           
           ,new MF_StartTag('dd')
           ,new MF_Text('Definition')
           ,new MF_EndTag('dd')
           
           ,new MF_StartTag('b') // test tag removal too
           ,new MF_EndTag('b')
            );
        $expect[1] = array(
            new MF_StartTag('dt')
           ,new MF_Text('Term')
           ,new MF_EndTag('dt')
           
           ,new MF_StartTag('dd')
           ,new MF_Text('Definition')
           ,new MF_EndTag('dd')
            );
        
        $inputs[2] = array(new MF_Text('How do you do!'));
        $expect[2] = false;
        
        // whitespace shouldn't trigger it
        $inputs[3] = array(
            new MF_Text("\n")
           ,new MF_StartTag('dd')
           ,new MF_Text('Definition')
           ,new MF_EndTag('dd')
           ,new MF_Text('       ')
            );
        $expect[3] = true;
        
        $inputs[4] = array(
            new MF_StartTag('dd')
           ,new MF_Text('Definition')
           ,new MF_EndTag('dd')
           ,new MF_Text('       ')
           ,new MF_StartTag('b')
           ,new MF_EndTag('b')
           ,new MF_Text('       ')
            );
        $expect[4] = array(
            new MF_StartTag('dd')
           ,new MF_Text('Definition')
           ,new MF_EndTag('dd')
           ,new MF_Text('       ')
           ,new MF_Text('       ')
            );
        $inputs[5] = array(
            new MF_Text('       ')
           ,new MF_Text("\t")
            );
        $expect[5] = false;
        
        $this->assertSeries($inputs, $expect, $def);
        
    }
    
    function test_required_pcdata_allowed() {
        $def = new HTMLDTD_ChildDef_Required('#PCDATA | b');
        $input = array(
            new MF_StartTag('b')
           ,new MF_Text('Bold text')
           ,new MF_EndTag('b')
           ,new MF_EmptyTag('img') // illegal tag
            );
        $expect = array(
            new MF_StartTag('b')
           ,new MF_Text('Bold text')
           ,new MF_EndTag('b')
           ,new MF_Text('<img />')
            );
        $this->assertEqual($expect, $def->validateChildren($input));
    }
    
    function test_optional() {
        $def = new HTMLDTD_ChildDef_Optional('b | i');
        $input = array(
            new MF_StartTag('b')
           ,new MF_Text('Bold text')
           ,new MF_EndTag('b')
           ,new MF_EmptyTag('img') // illegal tag
            );
        $expect = array(
            new MF_StartTag('b')
           ,new MF_Text('Bold text')
           ,new MF_EndTag('b')
            );
        $this->assertEqual($expect, $def->validateChildren($input));
        
        $input = array(
            new MF_Text('Not allowed text')
            );
        $expect = array();
        $this->assertEqual($expect, $def->validateChildren($input));
    }
    
}

class Test_PureHTMLDefinition extends UnitTestCase
{
    
    var $def, $lexer;
    
    function Test_PureHTMLDefinition() {
        $this->UnitTestCase();
        $this->def = new PureHTMLDefinition();
        $this->def->loadData();
        $this->lexer = new HTML_Lexer();
    }
    
    function test_removeForeignElements() {
        
        $inputs = array();
        $expect = array();
        
        $inputs[0] = array();
        $expect[0] = $inputs[0];
        
        $inputs[1] = array(
            new MF_Text('This is ')
           ,new MF_StartTag('b', array())
           ,new MF_Text('bold')
           ,new MF_EndTag('b')
           ,new MF_Text(' text')
            );
        $expect[1] = $inputs[1];
        
        $inputs[2] = array(
            new MF_StartTag('asdf')
           ,new MF_EndTag('asdf')
           ,new MF_StartTag('d', array('href' => 'bang!'))
           ,new MF_EndTag('d')
           ,new MF_StartTag('pooloka')
           ,new MF_StartTag('poolasdf')
           ,new MF_StartTag('ds', array('moogle' => '&'))
           ,new MF_EndTag('asdf')
           ,new MF_EndTag('asdf')
            );
        $expect[2] = array(
            new MF_Text('<asdf>')
           ,new MF_Text('</asdf>')
           ,new MF_Text('<d href="bang!">')
           ,new MF_Text('</d>')
           ,new MF_Text('<pooloka>')
           ,new MF_Text('<poolasdf>')
           ,new MF_Text('<ds moogle="&amp;">')
           ,new MF_Text('</asdf>')
           ,new MF_Text('</asdf>')
            );
        
        foreach ($inputs as $i => $input) {
            $result = $this->def->removeForeignElements($input);
            $this->assertEqual($expect[$i], $result);
            paintIf($result, $result != $expect[$i]);
        }
        
    }
    
    function test_makeWellFormed() {
        
        $inputs = array();
        $expect = array();
        
        $inputs[0] = array();
        $expect[0] = $inputs[0];
        
        $inputs[1] = array(
            new MF_Text('This is ')
           ,new MF_StartTag('b')
           ,new MF_Text('bold')
           ,new MF_EndTag('b')
           ,new MF_Text(' text')
           ,new MF_EmptyTag('br')
            );
        $expect[1] = $inputs[1];
        
        $inputs[2] = array(
            new MF_StartTag('b')
           ,new MF_Text('Unclosed tag, gasp!')
            );
        $expect[2] = array(
            new MF_StartTag('b')
           ,new MF_Text('Unclosed tag, gasp!')
           ,new MF_EndTag('b')
            );
        
        $inputs[3] = array(
            new MF_StartTag('b')
           ,new MF_StartTag('i')
           ,new MF_Text('The b is closed, but the i is not')
           ,new MF_EndTag('b')
            );
        $expect[3] = array(
            new MF_StartTag('b')
           ,new MF_StartTag('i')
           ,new MF_Text('The b is closed, but the i is not')
           ,new MF_EndTag('i')
           ,new MF_EndTag('b')
            );
        
        $inputs[4] = array(
            new MF_Text('Hey, recycle unused end tags!')
           ,new MF_EndTag('b')
            );
        $expect[4] = array(
            new MF_Text('Hey, recycle unused end tags!')
           ,new MF_Text('</b>')
            );
        
        $inputs[5] = array(new MF_StartTag('br', array('style' => 'clear:both;')));
        $expect[5] = array(new MF_EmptyTag('br', array('style' => 'clear:both;')));
        
        $inputs[6] = array(new MF_EmptyTag('div', array('style' => 'clear:both;')));
        $expect[6] = array(
            new MF_StartTag('div', array('style' => 'clear:both;'))
           ,new MF_EndTag('div')
            );
        
        // test automatic paragraph closing
        
        $inputs[7] = array(
            new MF_StartTag('p')
           ,new MF_Text('Paragraph 1')
           ,new MF_StartTag('p')
           ,new MF_Text('Paragraph 2')
            );
        $expect[7] = array(
            new MF_StartTag('p')
           ,new MF_Text('Paragraph 1')
           ,new MF_EndTag('p')
           ,new MF_StartTag('p')
           ,new MF_Text('Paragraph 2')
           ,new MF_EndTag('p')
            );
        
        $inputs[8] = array(
            new MF_StartTag('div')
           ,new MF_StartTag('p')
           ,new MF_Text('Paragraph 1 in a div')
           ,new MF_EndTag('div')
            );
        $expect[8] = array(
            new MF_StartTag('div')
           ,new MF_StartTag('p')
           ,new MF_Text('Paragraph 1 in a div')
           ,new MF_EndTag('p')
           ,new MF_EndTag('div')
            );
        
        // automatic list closing
        
        $inputs[9] = array(
            new MF_StartTag('ol')
            
           ,new MF_StartTag('li')
           ,new MF_Text('Item 1')
           
           ,new MF_StartTag('li')
           ,new MF_Text('Item 2')
           
           ,new MF_EndTag('ol')
            );
        $expect[9] = array(
            new MF_StartTag('ol')
            
           ,new MF_StartTag('li')
           ,new MF_Text('Item 1')
           ,new MF_EndTag('li')
           
           ,new MF_StartTag('li')
           ,new MF_Text('Item 2')
           ,new MF_EndTag('li')
           
           ,new MF_EndTag('ol')
            );
        
        foreach ($inputs as $i => $input) {
            $result = $this->def->makeWellFormed($input);
            $this->assertEqual($expect[$i], $result);
            paintIf($result, $result != $expect[$i]);
        }
        
    }
    
    function test_fixNesting() {
        $inputs = array();
        $expect = array();
        
        // some legal nestings
        
        $inputs[0] = array(
            new MF_StartTag('b'),
                new MF_Text('Bold text'),
            new MF_EndTag('b'),
            );
        $expect[0] = $inputs[0];
        
        // test acceptance of inline and block
        // as the parent element is considered FLOW
        $inputs[1] = array(
            new MF_StartTag('a', array('href' => 'http://www.example.com/')),
                new MF_Text('Linky'),
            new MF_EndTag('a'),
            new MF_StartTag('div'),
                new MF_Text('Block element'),
            new MF_EndTag('div'),
            );
        $expect[1] = $inputs[1];
        
        // test illegal block element -> text
        $inputs[2] = array(
            new MF_StartTag('b'),
                new MF_StartTag('div'),
                    new MF_Text('Illegal Div'),
                new MF_EndTag('div'),
            new MF_EndTag('b'),
            );
        $expect[2] = array(
            new MF_StartTag('b'),
                new MF_Text('<div>'),
                new MF_Text('Illegal Div'),
                new MF_Text('</div>'),
            new MF_EndTag('b'),
            );
        
        foreach ($inputs as $i => $input) {
            $result = $this->def->fixNesting($input);
            $this->assertEqual($expect[$i], $result);
            paintIf($result, $result != $expect[$i]);
        }
    }
    
}

?>