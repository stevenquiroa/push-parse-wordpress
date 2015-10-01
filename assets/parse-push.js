( function( window, undefined ) {
	'use strict'

	//global_vars
	var typingTimer;
	var max_length = ajax_object.max_length

	function submitForm(evt){
		evt.preventDefault()
		console.log('submitForm', this)
		var xhr = new XMLHttpRequest()
		var data = new FormData(this)
		var status = document.getElementById('status')
		status.style.color = 'inherit'
		status.innerHTML = 'Cargando...'
		data.append('security', ajax_object.ajax_nonce)
		console.log(data)
		xhr.open(this.method, ajax_object.ajax_url)
		xhr.responseType = 'json'
		xhr.timeout = 15000
		xhr.send(data)
		xhr.onreadystatechange = function () {
			if (xhr.readyState != 4) return;
			if (xhr.status == 200 && xhr.response.result) {
				status.style.color = 'green'
				status.innerHTML = 'Tu push ha sido enviada.'
			} else if(xhr.status == 500 || !xhr.response.result){
				status.style.color = 'red'
				status.innerHTML = '<b>Code: </b>' + xhr.response.code + '<br/><b>Message: </b>' + xhr.response.message + '<br/>Search possible error <a href="https://www.parse.com/docs/php/guide#errors">here</a>.'
			} else {
				status.style.color = 'red'
				status.innerHTML = 'Error desconocido'
			}
			console.log(xhr.response)
		}
	}
	function onKeyUp(evt){
		var self = this
		console.log('onKeyUp', evt, this.textLength)

		if (this.textLength >= max_length) {
			this.value = this.value.substr(0, max_length)
		}

		typingTimer = setTimeout(function(){
			document.getElementById('message_chars').innerHTML = max_length - self.textLength
		}, 200)
	}
	function onKeyDown (evt) {
		clearTimeout(typingTimer)
	}
	//vars
	var form = document.forms['sendpush']

	//functions
	console.log(form, form['message'], max_length)
	document.getElementById('message_chars').innerHTML = max_length - document.getElementById('message').textLength

	//Events
	form.addEventListener('submit', submitForm)
	form['message'].addEventListener('keyup', onKeyUp)
	form['message'].addEventListener('keydown', onKeyDown)
} )( this )
