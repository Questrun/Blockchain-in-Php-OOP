<?php
class Transaction{
var $amount;
var $payer;
var $payee;
function __construct($amount,$payer,$payee){
$this->amount=$amount;
$this->payer=$payer;
$this->payee=$payee;
}
function toString(){
return 	json_encode($this);
}
}

class Block{

var $prevHash;
var $transaction;
var $nonce;
var $ts;
function __construct($prevHash,$transaction){
$this->prevHash=$prevHash;
$this->transaction=$transaction;
$this->nonce=round(rand()*9999999999);
$this->ts=time();
}
function getHash(){
$str=json_encode($this);
return hash("sha256",$str,false);
}
}

class Chain{
	public static $instance=null;
	var $chain;
	function __construct(){
		$this->chain = Array( new Block('',new Transaction(100,'genesis','satoshi')) );
	}

	public static function getInstance():self{
		if(null === self::$instance)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	function lastBlock(){
		return $this->chain[count($this->chain) - 1];
	}
	function mine($nonce){
		$solution=1;
		echo "Mining";
		while(true){
		$attempt=hash('md5',($nonce+$solution),false);
		if(substr($attempt,0,4) === '0000'){
		    echo 'Solved'.$solution;
			return $solution;
		}
			$solution+=1;
		}
	}

	function addBlock($transaction,$senderPublicKey,$signature){
		if(openssl_verify($transaction->toString(),$signature,$senderPublicKey,"SHA256")){
		    echo "Adding Block";
			$newBlock= new Block($this->lastBlock()->getHash(), $transaction);
			array_push($this->chain,$newBlock);
		}
	}
}


class Wallet{
var $publicKey;
var $privateKey;
function __construct(){
$config=array('digest_alg' => 'sha256' ,'private_key_bits' => 2048 ,'private_key_type' => OPENSSL_KEYTYPE_RSA);
$rem=openssl_pkey_new($config);
$publickey_pem=openssl_pkey_get_details($rem);
$this->privateKey=openssl_get_privatekey($rem);
$this->publicKey=$publickey_pem['key'];
}

function sendMoney($amount,$payeePublicKey){
$transaction=new Transaction($amount,$this->publicKey,$payeePublicKey);
openssl_sign($transaction->toString(),$signature,$this->privateKey,"SHA256");
Chain::$instance->addBlock($transaction,$this->publicKey,$signature);
}
}

Chain::$instance=new Chain();
$satoshi=new Wallet();
$bob=new Wallet();
$satoshi->sendMoney(50,$bob->publicKey);
$bob->sendMoney(3,$satoshi->publicKey);
echo json_encode(Chain::$instance);
?>
