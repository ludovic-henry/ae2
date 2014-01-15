<?php

/*
 * Public domain.
 */

class u007xml
{
   var $arrOutput = array();
   var $resParser;
   var $strXmlData;


   function u007xml($tdata = "")
   {
       return $this->parse($tdata);
   }

   function parse($strInputXML)
   {
       $this->resParser = xml_parser_create ();
       xml_set_object($this->resParser,$this);
       xml_set_element_handler($this->resParser, "tagOpen", "tagClosed");

       xml_set_character_data_handler($this->resParser, "tagData");

       $this->strXmlData = xml_parse($this->resParser,$strInputXML );

       if(!$this->strXmlData) {
           die(sprintf("XML error: %s at line %d",
       xml_error_string(xml_get_error_code($this->resParser)),
       xml_get_current_line_number($this->resParser)));
       }

       xml_parser_free($this->resParser);

       return $this->arrOutput;
   }

   //called on each xml tree
   function tagOpen($parser, $name, $attrs) {
       $tag=array("nodename"=>$name,"attributes"=>$attrs);
       array_push($this->arrOutput,$tag);
   }

  //called on data for xml
   function tagData($parser, $tagData) {
       if(trim($tagData)) {
           if(isset($this->arrOutput[count($this->arrOutput)-1]['nodevalue'])) {
               $this->arrOutput[count($this->arrOutput)-1]['nodevalue'] .= $this->parseXMLValue($tagData);
           }
           else {
               $this->arrOutput[count($this->arrOutput)-1]['nodevalue'] = $this->parseXMLValue($tagData);
           }
       }
   }

  //called when finished parsing
   function tagClosed($parser, $name) {
       $this->arrOutput[count($this->arrOutput)-2]['childrens'][] = $this->arrOutput[count($this->arrOutput)-1];

       if(count ($this->arrOutput[count($this->arrOutput)-2]['childrens'] ) == 1)
       {
           $this->arrOutput[count($this->arrOutput)-2]['firstchild'] =& $this->arrOutput[count($this->arrOutput)-2]['childrens'][0];
       }
       array_pop($this->arrOutput);
   }

   function toArray()
   {
       //not used, we can call loadString or loadFile instead...
   }

   function parseXMLValue($tvalue)
   {
       //$tvalue = htmlentities($tvalue,ENT_NOQUOTES,"UTF-8");
       return $tvalue;
   }

   function displayXML()
   {
       print_r($this->arrOutput);
   }
}
?>
