function display_success() {
	document.getElementById("overlay").style.display="block";
	var box = document.getElementById("success_box");
	document.getElementById("sContent").innerHTML="This is the message you get if you succeeded";
	box.style.display = "block";
}

function display_failure() {
	document.getElementById("overlay").style.display="block";
	var box = document.getElementById("fail_box");
	document.getElementById("fContent").innerHTML="This is the message you get if you failed";
	box.style.display = "block";
}
function display_success1() {
	document.getElementById("overlay").style.display="block";
	var box = document.getElementById("success_box");
	
	var boxhead = document.createElement("h1");
	box.appendChild(boxhead);
	boxhead.innerHTML='Success!';
	
	var boxtext = document.createElement("p");
	box.appendChild(boxtext);
	boxtext.innerHTML='This is the message you get if you succeeded';

	box.style.display = "block";
}

function display_failure1() {
	document.getElementById("overlay").style.display="block";
	var box = document.getElementById("fail_box");
	
	var boxhead = document.createElement("h1");
	boxhead.innerHTML="Epic Fail!";
	box.appendChild(boxhead);
	
	var boxtext = document.createElement("p");
	box.appendChild(boxtext);
	boxtext.innerHTML="This is the message you get if you failed";

	box.style.display = "block";
	
}
  
function close() {
	
  	document.getElementById("success_box").style.display="none";
	document.getElementById("fail_box").style.display="none";
	document.getElementById("overlay").style.display="none";
}