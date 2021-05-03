function toolBtn(prefix,suffix) {
	var el = document.getElementById("message");
	if (document.selection) { //IE-like
		el.focus();
		document.selection.createRange().text=prefix+document.selection.createRange().text+suffix;
	} else if (typeof el.selectionStart != undefined) { //FF-like
		el.value=el.value.substring(0,el.selectionStart)+prefix+el.value.substring(el.selectionStart,el.selectionEnd)+suffix+el.value.substring(el.selectionEnd,el.value.length);
		el.focus();
	}
}