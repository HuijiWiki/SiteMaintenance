<?php

abstract class BaseSite{


     abstract protected function checkRule();
   
     abstract protected function install();

     abstract protected function update();
 
     abstract protected function remove();

}


?>
