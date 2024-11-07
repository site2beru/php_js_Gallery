 // Функция, создающая прогресс-бар
    function createProgressBar() {
        let progressBar = document.createElement('div');
        progressBar.className = 'progress-bar';
        progressBar.style.width = '0%';
        return progressBar;
    }



    document.addEventListener('DOMContentLoaded', function() {
        const closeButtons = document.querySelectorAll('.gal_close-button');
        const statusIndicator = document.getElementById('upload-status');
        const addbutton = document.getElementById('update_gal');
        let totalFilesCount = 0;
        let uploadedFilesCount = 0;
        let maxFiles = 20;


        let edit_btn = document.getElementById('gal_edit');
        let add_image = document.getElementById('add-image');
        let popup_gallery = document.getElementById('popup-gallery');
        if (edit_btn) {
            edit_btn.addEventListener('click', () => {
                add_image.style.display = add_image.style.display === 'none' ? 'flex' : 'none';
                popup_gallery.style.height = popup_gallery.style.height === 'auto' ? 'auto' : 'auto';
                const closeButtons = document.querySelectorAll('.gal_close-button');
                if (closeButtons.length > 0) {
                    closeButtons.forEach(button => {
                        button.style.display = button.style.display === 'none' ? 'block' : 'none';
                    });
                }
            });
        }


        // Удаляем картинки
        closeButtons.forEach(button => {
            button.addEventListener('click', () => {
                addbutton.style.display = 'block';
                const imageElement = button.closest('.event__main-gallery__block-img');
                if (imageElement) {
                    const imageUrl = imageElement.style.backgroundImage.slice(5, -2);
                    const imagePath = imageUrl.split('/').pop();

                    let fileTextarea = document.getElementById('file-textarea');
                    if (fileTextarea) {
                        // Текущий список файлов
                        let currentFiles = fileTextarea.innerHTML.split(' ').filter(file => file.trim() !== '');

                        // Удаление выбранного файла из списка
                        currentFiles = currentFiles.filter(file => file !== imagePath);

                        // Обновление содержимого textarea
                        fileTextarea.innerHTML = currentFiles.join(' ');

                        if (fileTextarea.innerHTML.startsWith(' ')) {
                            fileTextarea.innerHTML = fileTextarea.innerHTML.substring(1).trim();
                        }
                    }

                    // Удаляем изображение из галереи
                    imageElement.remove();
                }
            });
        });


        // Добавляем
        var input = document.getElementById('gallery-input');

        input.onchange = function(e) {
            if (!e.target || !e.target.files) {
                console.error('No files selected');
                return;
            }

            var files = e.target.files;
            if (files.length > maxFiles) {
                alert(`Вы не можете загрузить более ${maxFiles} изображений за раз.`);
                return;
            }
            totalFilesCount = files.length;
            uploadedFilesCount = 0;
            Array.from(files).forEach((file) => {
                if (!file || !file.type || !file.type.startsWith('image/')) {
                    console.error(`File ${file.name} is not an image`);
                    return;
                }
                try {
                    uploadFile(file);
                } catch (error) {
                    console.error('Error uploading file', error);
                }
            });

            addbutton.style.display = 'block';

            if (totalFilesCount == maxFiles) {
                add_image.remove();
            }

            function updateStatus() {
                if (statusIndicator) {
                    statusIndicator.textContent = `Загружено ${uploadedFilesCount} из ${totalFilesCount}`;
                }
            }

            function uploadFile(file) {
                // Создаем прогресс-бар для каждого файла
                let progressBar = createProgressBar();
                let textArea = document.getElementById('file-textarea');

                textArea.innerHTML += file.name + ' ';

                // Сжимаем большие размеры компрессором

                new Compressor(file, {
                    quality: 0.6,
                    maxSizeMB: 2,
                    maxWidth: 1200,
                    maxHeight: 800,
                    success(result) {

                        let reader = new FileReader();
                        reader.onload = function() {
                            let imageData = reader.result;

                            // превью изображения
                            let image = document.createElement('div');
                            image.className = 'event__main-gallery__block-img';
                            image.style.backgroundImage = `url(${imageData})`;
                            var closeButton = document.createElement('span');
                            closeButton.className = 'gal_close-button';
                            closeButton.innerHTML = '×';
                            image.appendChild(closeButton);
                            closeButton.addEventListener('click', function() {
                                var image = this.parentNode;
                                image.remove();
                            });

                            //Превью перед кнопкой добавления изображений
                            var addImageButton = document.getElementById('add-image');
                            if (addImageButton) {
                                addImageButton.before(image);
                            }

                            // Добавляем прогресс-бар в превью
                            image.appendChild(progressBar);
                            let formData = new FormData();

                            formData.append('gallery[]', result, result.name);

                            $.ajax({
                                url: '',
                                type: 'POST',
                                action: 'upload',
                                gallery: formData,
                                contentType: false,
                                processData: false,

                                xhr: function() {
                                    var xhr = new window.XMLHttpRequest();

                                    xhr.upload.addEventListener("progress", function(evt) {
                                        if (evt.lengthComputable) {
                                            var percentComplete = (evt.loaded / evt.total) * 100;
                                            progressBar.style.width = percentComplete + '%';
                                        }
                                    }, false);

                                    return xhr;
                                },

                                success: function(response) {
                                    try {
                                        const jsonResponse = JSON.parse(response);

                                        if (jsonResponse.status === 'success') {
                                            uploadedFilesCount++;
                                            updateStatus();
                                        } else {
                                            throw new Error(jsonResponse.message || 'Неизвестная ошибка');
                                        }
                                    } catch (e) {
                                        // console.error('Ошибка при разборе ответа:', e.message);
                                        // alert(`Ошибка при загрузке файла ${result.name}: ${e.message}`);
                                    }
                                },

                                error: function(xhr, status, error) {
                                    console.error('Ошибка загрузки:', error);
                                    alert(`Ошибка при загрузке файла ${result.name}`);
                                    updateStatus();
                                }
                            });
                        };

                        reader.readAsDataURL(result);
                    },
                    error(err) {
                        console.log(err.message);
                        alert(`Ошибка обработки файла ${file.name}: ${err.message}`);
                        updateStatus(); // Обновляем статус даже в случае ошибки обработки ибо нехрен
                    }
                });
            }
        };
    });
