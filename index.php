<?php

$profile = "SELECT * FROM *TABLE_NAME* WHERE id = {$user->id}";
$profile = $profile->fetch_assoc();
foreach ($profile as $key => $value) {
    $key = $value;
}

?>


<form id="<?php echo cmsForm::getCSRFToken(); ?> action="" class=" master__form" method="POST" enctype="multipart/form-data">

    <div class="event__main-gallery">
        <h2 class="event__main-info__title">Галерея</h2>
            <span id="gal_edit" style="cursor:pointer; color: #0392F5; font-size:12px;">редактировать галерею 
            <svg fill="#000000" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="15px" height="15px" viewBox="0 0 494.936 494.936" xml:space="preserve">
                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                <g id="SVGRepo_iconCarrier">
                    <g>
                        <g>
                            <path d="M389.844,182.85c-6.743,0-12.21,5.467-12.21,12.21v222.968c0,23.562-19.174,42.735-42.736,42.735H67.157 c-23.562,0-42.736-19.174-42.736-42.735V150.285c0-23.562,19.174-42.735,42.736-42.735h267.741c6.743,0,12.21-5.467,12.21-12.21 s-5.467-12.21-12.21-12.21H67.157C30.126,83.13,0,113.255,0,150.285v267.743c0,37.029,30.126,67.155,67.157,67.155h267.741 c37.03,0,67.156-30.126,67.156-67.155V195.061C402.054,188.318,396.587,182.85,389.844,182.85z"></path>
                            <path d="M483.876,20.791c-14.72-14.72-38.669-14.714-53.377,0L221.352,229.944c-0.28,0.28-3.434,3.559-4.251,5.396l-28.963,65.069 c-2.057,4.619-1.056,10.027,2.521,13.6c2.337,2.336,5.461,3.576,8.639,3.576c1.675,0,3.362-0.346,4.96-1.057l65.07-28.963 c1.83-0.815,5.114-3.97,5.396-4.25L483.876,74.169c7.131-7.131,11.06-16.61,11.06-26.692 C494.936,37.396,491.007,27.915,483.876,20.791z M466.61,56.897L257.457,266.05c-0.035,0.036-0.055,0.078-0.089,0.107 l-33.989,15.131L238.51,247.3c0.03-0.036,0.071-0.055,0.107-0.09L447.765,38.058c5.038-5.039,13.819-5.033,18.846,0.005 c2.518,2.51,3.905,5.855,3.905,9.414C470.516,51.036,469.127,54.38,466.61,56.897z"></path>
                        </g>
                    </g>
                </g>
            </svg></span>
        <div class="popup-gallery" id="popup-gallery">
            <div class="event__main-gallery__block ">
                <?php
                $galleryQuery = "SELECT gallery FROM cms_users WHERE id = {$profile['id']}";
                $db = cmsDatabase::getInstance();
                $result = $db->query($galleryQuery);

                if ($result) {
                    $row = $result->fetch_assoc();
                    if ($row && isset($row['gallery'])) {
                        $galleryFiles = explode(' ', $row['gallery']);
                        foreach ($galleryFiles as $image) {
                            if ($image) {
                                $image = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
                                $imageUrl = '/upload/000/u' . $profile['id'] . '/gallery/' . trim($image);
                                echo "<div class='event__main-gallery__block-img' style='background-image: url(\"{$imageUrl}\");'>
                                <a href='{$imageUrl}' class='event__main-gallery__block-img' style='height:100%; background-image: url(\"{$imageUrl}\");'></a>
                                <span class='gal_close-button' style='display:none;'>x</span>
                                </div>";
                            }
                        }
                    }
                }
                ?>

                <input type="file" max-size="500" name="gallery[]" id="gallery-input" class="gallery_input" multiple accept="image/jpeg, image/jpg, image/png, image/webp" style="opacity:0; position: absolute">
                <label for="gallery-input" class="event__main-gallery__block-img event__main-gallery__block-img_add" id="add-image" style='display:none;'>+</label>

                <textarea id="file-textarea" rows="5" cols="50" readonly name="galFiles" style="display: none;"><?php if ($galleryFiles) foreach ($galleryFiles as $image) echo $image . ' '; ?></textarea>
                
            </div>
        </div>
    </div>

    <? #php } 
    ?>

    <div class="event__main-info__block-btn">
        <button id="update_gal" class="event__main-info__block-btn__button" type="submit" style="display: none;">Обновить галерею</button>
    </div>
</form>
<script src="../script.js">
</script>

<?php
# Собственно обработка всего безобразия

if (isset($_POST) && !empty($_POST)) {


    $user_id = $user->id; // ID пользователя хранится в сессии после авторизации.

    $gal_sql = [];

    // Получаем текущую галерею пользователя
    $currentGalleryQuery = "SELECT gallery FROM cms_users WHERE id = {$user_id}";
    $currentGalleryResult = cmsDatabase::getInstance()->query($currentGalleryQuery);
    $currentGalleryRow = $currentGalleryResult->fetch_assoc();

    if (isset($currentGalleryRow['gallery'])) {
        $currentGallery = explode(' ', $currentGalleryRow['gallery']);
    } else {
        $currentGallery = [];
    }

    // Проверяем наличие Imagick
    if (!extension_loaded('imagick')) {
        echo json_encode(['status' => 'error', 'message' => 'Imagick extension is not installed.']);
        exit;
    }

    // Обрабатываем загруженные файлы
    if (isset($_FILES['gallery']) && !empty($_FILES['gallery']['name'])) {
        foreach ($_FILES['gallery']['name'] as $key => $name) {
            if (!empty($name)) {
                $dir_name = 'u' . $user->id . '/gallery';
                $file_name = basename($name);

                // Создаем директорию, если она не существует
                $dir_path = "upload/000/$dir_name";
                if (!is_dir($dir_path)) {
                    mkdir($dir_path, 0777, true);
                }

                // Используем Imagick для обработки изображения
                try {
                    $imagePath = $_FILES['gallery']['tmp_name'][$key];
                    $imagick = new \Imagick($imagePath);

                    // Изменяем размер изображения до 1200x800
                    $imagick->resizeImage(1200, 800, Imagick::FILTER_LANCZOS, 1, true);

                    // Устанавливаем качество изображения для уменьшения размера файла
                    $imagick->setImageCompressionQuality(85);

                    // Сохраняем обработанное изображение
                    $outputPath = "$dir_path/$file_name";
                    if ($imagick->writeImage($outputPath)) {
                        // Добавляем новое изображение в массив
                        $gal_sql[] = $file_name;
                    }

                    // Очистка памяти
                    $imagick->clear();
                } catch (\Exception $e) {
                    echo json_encode(['status' => 'error', 'message' => 'Ошибка при обработке изображения: ' . $e->getMessage()]);
                }
            }
        }
    }

    // Объединяем текущие изображения с новыми
    $updatedGallery = array_unique(array_merge($currentGallery, $gal_sql));
    $galFiles = $_POST['galFiles']; // Имена файлов берем из textarea для корректного их добавления и удаления

    // Если все было удалено или ничего не загружено
    if (empty($galFiles)) {
        // Очистка галереи
        $updateQuery = "UPDATE cms_users SET gallery = NULL WHERE id = {$user_id}";
    } else {
        // Преобразуем окончательный список имен файлов в строку
        $gal_string = implode(' ', $galFiles);

        // Выполняем запрос на обновление базы данных с новым списком изображений
        $updateQuery = "UPDATE cms_users SET gallery = '$galFiles' WHERE id = {$user_id}";
    }

    // Сканируем папку и удаляем лишние файлы
    if (is_dir($dir_path)) {
        foreach (glob("$dir_path/*") as $file_in_folder) {
            if (!in_array(basename($file_in_folder), $updatedGallery)) {
                if (file_exists($file_in_folder) && is_file($file_in_folder)) {
                    unlink($file_in_folder); // Удаляем файл
                }
            }
        }
    }

    try {
        cmsDatabase::getInstance()->query($updateQuery);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Ошибка при обновлении галереи: ' . $e->getMessage()]);
    }
    header("Location: /users/" . $user->id);
}
?>
