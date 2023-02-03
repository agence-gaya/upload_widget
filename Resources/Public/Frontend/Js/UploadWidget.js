(function() {
	if (checkBrowserCompatibility()) {
		const uploadProgressFields = document.getElementsByClassName('upload-progress-field');
		for (let i = 0; i < uploadProgressFields.length ; i++) {
			uploadProgressFields[i].addEventListener('change', upload);
		}
		const uploadResets = document.getElementsByClassName('upload-reset');
		for (let i = 0; i < uploadResets.length ; i++) {
			uploadResets[i].addEventListener('click', resetUpload);
		}
	}
})();

function checkBrowserCompatibility() {
	const xhttp = new XMLHttpRequest();
	if (!xhttp.upload || typeof xhttp.upload === 'undefined') {
		alert("Upload progress is not supported. Please use an up to date browser.");
		return false;
	}

	return true;
}

function upload(e) {
	e.preventDefault();
	const fileInput = e.target;
	const propertyBase = fileInput.getAttribute('id').slice(0, -7);
	const field = document.getElementById(propertyBase);
	const progress = document.getElementById(propertyBase + '_progress');
	const uploadResult = document.getElementById(propertyBase + '_result');
	const uploadError = document.getElementById(propertyBase + '_error');
	const uploadReset = document.getElementById(propertyBase + '_reset');
	const parentForm = field.closest('form');

	// Check how many file has been selected
	if (fileInput.files === undefined || fileInput.files.length == 0) {
		return;
	}

	parentForm.addEventListener('submit', blockSubmit);

	// We only manage one file
	const file = fileInput.files[0];
	fileInput.style.display = 'none';
	progress.parentElement.style.display = 'block';
	uploadResult.parentElement.style.display = 'none';

	const data = new FormData();
	data.append('tx_uploadwidget_upload[file]', file);

	const xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			// Success
			const response = JSON.parse(this.responseText);
			fileInput.style.display = 'none';
			progress.parentElement.style.display = 'none';
			uploadResult.parentElement.style.display = 'none';
			uploadError.style.display = 'none';

			if (response.status === "success") {
				uploadResult.parentElement.style.display = 'block';
				uploadResult.innerHTML = '<a href="' + response.fileDownload + '" target="_blank">' + response.filename + '</a>';
				field.value = response.fileUid;
				uploadReset.setAttribute('data-uri', response.resetAction);
				fileInput.parentElement.setAttribute('class', fileInput.parentElement.getAttribute('class').replace('has-error', ''));
			} else {
				if (!fileInput.parentElement.getAttribute('class').match(/has-error/)) {
					fileInput.parentElement.setAttribute('class', fileInput.parentElement.getAttribute('class') + ' has-error');
				}
				uploadError.innerHTML = '';
				fileInput.style.display = 'block';
				fileInput.value = '';
				uploadError.style.display = 'block';
				uploadResult.innerText = '';
				field.value = '';
				for (let i = 0 ; i < response.errors.length ; i++) {
					uploadError.innerHTML = (uploadError.innerHTML ? uploadError.innerHTML + '<br>' : '') + response.errors[i];
				}
				uploadReset.setAttribute('data-uri', '');
			}

			parentForm.removeEventListener('submit', blockSubmit);
		}
	};
	xhttp.upload.addEventListener('progress', function(evt) {
		if (evt.lengthComputable) {
			const percentComplete = (evt.loaded / evt.total) * 100;
			progress.value = percentComplete;
			progress.innerText = percentComplete + '%';
		}
	}, false);
	xhttp.open('POST', fileInput.getAttribute('data-uri'));
	xhttp.send(data);
}

function blockSubmit(e) {
	e.preventDefault();
}

function resetUpload(e) {
	const propertyBase = e.target.getAttribute('id').slice(0, -6);
	const field = document.getElementById(propertyBase);
	const fileInput = document.getElementById(propertyBase + '_upload');
	const progress = document.getElementById(propertyBase + '_progress');
	const uploadResult = document.getElementById(propertyBase + '_result');

	if (e.target.getAttribute('data-uri')) {
		const xhttp = new XMLHttpRequest();
		xhttp.open('POST', e.target.getAttribute('data-uri'));
		xhttp.send();
	}

	field.value = '';
	fileInput.value = '';
	fileInput.style.display = 'block';
	progress.parentElement.style.display = 'none';
	uploadResult.parentElement.style.display = 'none';
}
