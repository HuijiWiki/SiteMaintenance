<?php
interface WebSocket
{

	public function sendMessage($conn,$data);
	public function receiveMessage($conn,$data);
}

?>
