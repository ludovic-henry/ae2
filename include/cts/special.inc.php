<?php

function telephone_userinput ( $tel )
{
  $tel = ereg_replace("^\+([0-9]*)([^0-9\+]*)\(0\)(.*)$","+\\1\\3",$tel);

  $tel = ereg_replace("[^0-9\+]","",$tel);

  if ( $tel != "" && !ereg("^\+",$tel) )
    $tel = "+33".substr($tel,1);

  return $tel;
}

function telephone_display ( $tel )
{
  if ( ereg("^\(+33|0)([0-9])([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})$",$tel,$regs) )
    $tel_format = "0".$regs[1]." ".$regs[2]." ".$regs[3]." ".$regs[4]." ".$regs[5];
  else
    $tel_format = $tel;

  return "<a href=\"tel:".$tel."\">".$tel_format."</a>";
}

/*
echo telephone_userinput("06 67 01 32 82")."\n";
echo telephone_userinput("06.67.01.32.82")."\n";
echo telephone_userinput("+33 6 67 01 32 82")."\n";
echo telephone_userinput("+33 (0)6 67 01 32 82")."\n";
echo telephone_userinput("+33.(0).6.67.01.32.82")."\n";
echo telephone_userinput("+33(0).6.67.01.32.82")."\n";
echo "\n";
echo telephone_display("+33667013282")."\n";
*/


?>
