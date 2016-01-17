function validateEmail(email) {
    var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}

function validate() {
    var blogName = document.getElementById('blogName').value;
    var email = document.getElementById('email').value;
    if (blogName == '' && email == '') {
		alert(error2);
		return false;
	} else if (blogName == '') {
		alert(error0);
		return false;
	} else if (email == '') {
		alert(error1);
		return false;
	} else if (!validateEmail(email)) {
		alert(error3 + email + error4);
		return false;
    } 
    return true;
}
