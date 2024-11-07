
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
