function createFileDropbox(options) {
	
    const iconByExtension = {
        'doc': 'word',
        'docx': 'word',
        'odt': 'word',
        'rtf': 'word',
        'ppt': 'powerpoint',
        'pptx': 'powerpoint',
        'odp': 'powerpoint',
        'xls': 'excel',
        'xlsx': 'excel',
        'ods': 'excel',
        'pdf': 'pdf',
        'wav': 'audio',
        'mp3': 'audio',
        'ogg': 'audio',
        'flac': 'audio',
        'zip': 'archive',
        'rar': 'archive',
        '7z': 'archive',
        'tar': 'archive',
        'gz': 'archive',
        'tgz': 'archive',
        'html': 'code',
        'js': 'code',
        'css': 'code',
        'jpg': 'image',
        'jpeg': 'image',
        'png': 'image',
        'bmp': 'image',
        'gif': 'image'
    };
    
	const mouseInEvents = ['dragover', 'dragenter'];
	const mouseOutEvents = ['dragleave', 'dragend', 'drop'];
    
    const containerId = options.id || 0;
    const inputName = options.name || '';
    const maxSize = options.maxSize || 4*1024*1024;
    const uploadUrl = options.uploadUrl || '';
    const removeUrl = options.removeUrl || '';
    const biggerThanText = options.biggerThanText || 'Bigger than';
    const removeText = options.removeText || 'Remove';
    const removeConfirmText = options.removeConfirmText || 'Are you sure, you want to remove?';
    const hideText = options.hideText || 'Hide';
    const filesData = options.filesData || [];
    
    let fileDropbox = document.querySelector('#' + containerId + ' .file-dropbox');
    let fileList = document.querySelector('#' + containerId + ' .file-dropbox-list');
	let uploadLink = document.querySelector('#' + containerId + ' .file-dropbox-upload-link');
    let fileInput = document.querySelector('#' + containerId + ' input[type=file]');
    
    function mouseIn(event) {
        event.preventDefault();
        let types = event.dataTransfer.types;
        for (let i = 0; i < types.length; i++) {
            if (types[i] === 'text/plain') {
                return;
            }
        }
        fileDropbox.classList.add('file-dropbox-over');
    }
    
    function mouseOut(event) {
        event.preventDefault();
        fileDropbox.classList.remove('file-dropbox-over');
    }
    
    function dropFiles(event) {
        event.preventDefault();
        let files = event.dataTransfer.files;
        //console.log(files); // in Edge this is always empty.. why?
		for (let i = 0; i < files.length; i++) {
			uploadFile(files[i]);
		}        
    }
    
    function addRemoveLink(item, fileName, name) {
        let icon = document.createElement('i');
        let link = document.createElement('a');
        let text = document.createElement('span');
        let url = new URL(removeUrl);
        url.searchParams.set('name', name);
        icon.classList.add('fas');
        icon.classList.add('fa-trash');
        text.textContent = removeText;
        link.classList.add('remove-link');
        link.addEventListener('click', function() {
            if (!confirm(removeConfirmText)) {
                return;
            }
            let xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);
            xhr.onload = function() {
                if (this.status !== 200) {
                    return setError(item, fileName + ' - Remove error (Status: ' + this.status + ')');
                } else {
                    item.remove();
                }
            };
            xhr.send();            
        });
        link.appendChild(text);
        link.appendChild(icon);
        item.appendChild(link);
    }
     
    function setError(item, message) {
        let messageNode = document.createTextNode(message);
        let icon = document.createElement('i');
        icon.classList.add('icon');
        icon.classList.add('fas');
        icon.classList.add('fa-exclamation-triangle');
        item.textContent = '';
        item.appendChild(icon);
        item.appendChild(messageNode);        
        item.style.color = '#a00';
        addHideLink(item);
        addClearDiv(item);
    }
    
    function addHideLink(item) {
        let icon = document.createElement('i');
        let link = document.createElement('a');
        let text = document.createElement('span');
        icon.classList.add('fas');
        icon.classList.add('fa-times');
        text.textContent = hideText;
        link.classList.add('remove-link');
        link.appendChild(text);
        link.appendChild(icon);
        item.appendChild(link);
        link.addEventListener('click', function() {
            item.remove();
        });
    }
    
    function setFile(item, fileName, name) {
        let iconClass = 'fa-file';
        let icon = document.createElement('i');
        let nameNode = document.createTextNode(fileName);
        let link = document.createElement('a');
        let href = 'upload/' + name[0] + name[1] + '/' + name[2] + name[3] + '/' + name;
        let ext = fileName.substr(fileName.lastIndexOf('.') + 1);
        if (iconByExtension.hasOwnProperty(ext)) {
            iconClass = 'fa-' + iconByExtension[ext];
        }
        icon.classList.add('icon');
        icon.classList.add('fas');
        icon.classList.add(iconClass);
        link.classList.add('target-link');
        link.setAttribute('target', '_blank');
        link.setAttribute('href', href);
        link.appendChild(nameNode);
        item.textContent = '';
        item.appendChild(icon);
        item.appendChild(link);        
        addRemoveLink(item, fileName, name);
        if (inputName) {
            addHiddenInput(item, inputName, name);        
        }
        addClearDiv(item);
    }
    
    function addClearDiv(item) {
        let div = document.createElement('div');
        div.style.clear = 'both';
        item.appendChild(div);
    }
    
    function addHiddenInput(item, name, value) {
        let hidden = document.createElement('input');
        hidden.setAttribute('type', 'hidden');
        hidden.setAttribute('name', name + '[]');
        hidden.value = value; 
        item.append(hidden);
    }
    
    function createFileListItem() {
        let fileListItem = document.createElement('li');
        fileList.appendChild(fileListItem);
        return fileListItem;
    }
    
    function uploadFile(file) {        
        const maxMB = Math.round(maxSize / 1024 / 1024);
        let formData = new FormData();
        let fileListItem = createFileListItem();
        let xhr = new XMLHttpRequest();
        if (file.size > maxSize) {
            return setError(fileListItem, file.name + ' - ' + biggerThanText + ' ' + maxMB + 'MB.');
        }
        fileListItem.textContent = file.name;        
        formData.append('file', file);
        xhr.open('POST', uploadUrl, true);                
        xhr.upload.onprogress = function (event) {
            if (!event.lengthComputable) {
                return;
            }
            let percentComplete = (event.loaded / event.total) * 100;
            fileListItem.textContent = file.name + ' - ' + percentComplete + '%';
        };        
        xhr.onload = function() {
            if (this.status !== 200) {
                return setError(fileListItem, file.name + ' - Upload error (Status: ' + this.status + ')');
            }
            json = JSON.parse(this.responseText);
            if (!json || json.error) {
                return setError(fileListItem, file.name + ' - ' + json.error);
            }
            filesData.push(file);
            setFile(fileListItem, file.name, json.name);
        };        
        xhr.send(formData);
    }
        
    // add event listeners
	mouseInEvents.forEach(function (eventName) {
		fileDropbox.addEventListener(eventName, mouseIn);
	});
	mouseOutEvents.forEach(function (eventName) {
		fileDropbox.addEventListener(eventName, mouseOut);
	});    
	fileDropbox.addEventListener('drop', dropFiles);    
    uploadLink.addEventListener('click', function() {
        fileInput.click();
    });    
    fileInput.addEventListener('change', function() {
        uploadFile(fileInput.files[0]);
    });    
    
    // add existing files
    filesData.forEach(function (fileData) {
        let fileListItem = createFileListItem();
        setFile(fileListItem, fileData.original_name, fileData.name);
    });
}