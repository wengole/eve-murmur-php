function display_success(text) {
	document.getElementById("overlay").style.display="block";
	var box = document.getElementById("success_box");
	document.getElementById("sContent").innerHTML=text;
	box.style.display = "block";
}

function display_failure(text) {
	document.getElementById("overlay").style.display="block";
	var box = document.getElementById("fail_box");
	document.getElementById("fContent").innerHTML=text;
	box.style.display = "block";
}
  
function close() {
	
  	document.getElementById("success_box").style.display="none";
	document.getElementById("fail_box").style.display="none";
	document.getElementById("overlay").style.display="none";
}