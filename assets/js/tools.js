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

function submit_on_return(event,act){
	a=event.keyCode?event.keyCode:event.which?event.which:event.charCode;
	document.mod.action.value=act;
	document.mod.arg.value=document.mod.tmp.value;
	if (a==13) document.mod.submit();
}

function movetid() {
	var x = document.getElementById('forumselect').selectedIndex;
	document.getElementById('move').innerHTML = document.getElementsByTagName('option')[x].value;
	return document.getElementsByTagName('option')[x].value;
}
function renametitle() {
	var x = document.getElementById('title').value;
	document.getElementById('rename').innerHTML = document.getElementsByTagName('input')[x].value;
	return document.getElementsByTagName('input')[x].value;
}
function trashConfirm(e) {
	if (confirm('Are you sure you want to trash this thread?'));
	else {
		e.preventDefault();
	}
}

function submitmod(act){
	document.getElementById('action').value=act;
	document.getElementById('mod').submit();
}
function submitrename(name){
	document.mod.arg.value=name;
	submitmod('rename')
}
function submitmove(fid){
	document.mod.arg.value=fid;
	submitmod('move')
}