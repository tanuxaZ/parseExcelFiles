<?php

class parseFile
{
    const ALLOWED_TYPE = array('xml', 'zip');
    const FILE_UPLOAD_MAX_SIZE = '2000'; //кб
    const PATH_UNPACK_FILE = './uploads/';
    const LIFE_TIME_UNPACK_FILE = 7200;


    public $error = array();

    private $fileTemp;
    private $fileSize;
    private $fileType;
    private $fileName;
    private $fileExt;

    private $xmlObj = null;

    /**
     * Парсит XML Объект, возвращает массив заголовков колонок постранично
     *
     * @param $xmlObj - xml объект файла
     * @return array|bool - массив заголовков, если ок, иначе - false
     * $titleArr = array(
     *      '0' => array('Name', 'LastName', 'Email', 'City') - первый лиси excel документа
     *      '1' => array('City', 'PhoneCode') - второй лиси excel документа
     * )
     */
    public function getTitleArray()
    {
        $titles = array();

        if (!$this->xmlObj) {
           return false;
        }

        $key = 0;
        foreach ($this->xmlObj->Worksheet as $list) {
            $row = $list->Table->Row[0];
            if (is_object($row) && is_object($row->Cell)) {
                foreach ($row->Cell as $cell) {
                    $titles[$key][] = strval($cell->Data);
                }
                $key++;
            }
        }

        return $titles;
    }

    /**
     * Парсит XML Объект, возвращает массив данных постранично
     *
     * @param $xmlObj - xml объект файла
     * @return array|bool - массив данных, если ок, иначе - false
     * $dataArr = array(
     *      '0' => array(    - первый лиси excel документа
     *          '0' => array('Name1', 'LastName1', 'Email1', 'City1'), - первая строка данных
     *          '1' => array('Name2', 'LastName2', 'Email2', 'City2'), - вторая строка данных
     *      ),
     *      '1' => array(    - второй лиси excel документа
     *          '0' => array('City1', 'PhoneCode1'), - первая строка данных
     *      )
     * )
     */
    public function getDataArray()
    {
        $dataArray = array();

        if (!$this->xmlObj) {
            return false;
        }

        $listNum = 0;

        foreach ($this->xmlObj->Worksheet as $list) {
            $rowNum = 0;

            foreach ($list->Table->Row as $key => $row) {
                if ($rowNum == 0) {
                    $rowNum ++;
                    continue;
                }

                foreach ($row->Cell as $cell) {
                    $dataArray[$listNum][$rowNum][] = strval($cell->Data);
                }

                $rowNum ++;
            }

            $listNum ++;
        }

        return $dataArray;
    }

    /**
     * Преобразование XML файла в объект
     *
     * @param $filePath
     * @return bool|SimpleXMLElement - возвращает false, если не удалось считать файл,
     * иначе - объект файла
     */
    public function getXMLObj($field)
    {
        $this->deleteUnpackFile();

        if (!($filePath = $this->checkFile($field))) {
            return false;
        }

        if ($this->fileExt == '.zip'){
            $nameFile = $this->unpackZip($filePath);
            $filePath = self::PATH_UNPACK_FILE . $nameFile;
        }

        $xmlObj = simplexml_load_file($filePath);

        if(!$xmlObj){
            $this->error[] = "Не удалось получить содержимое файла";
            return false;
        }

        $this->xmlObj = $xmlObj;

        return true;
    }

    /**
     * Распаковка zip архива
     *
     * @param $pathFile - путь к архиву
     * @return bool|string - путь и имя к файлу, если распаковка прошла удачно, иначе - false
     */
    private function unpackZip($pathFile)
    {
        $zip = new ZipArchive();
        $res = $zip->open($pathFile);

        if ($res) {
            $zip->extractTo(self::PATH_UNPACK_FILE);
            $nameFile = $zip->getNameIndex(0);
            $zip->close();
            return  $nameFile;
        }

        $this->error[] = 'Распаковка архива не удачная';
        return false;
    }

    /**
     * Валидация загружаемого файла
     *
     * @param $field - имя файла поля в форме
     * @return bool|string - fasle - если файл не соответствует требованиям,
     * иначе - путь к файлу
     */
    private function checkFile($field)
    {
        if (!isset($_FILES[$field])) {
            $this->error[] = "Файл не загружен.";
            return false;
        }

        $thisFile = $_FILES[$field];

        if (!$this->isFileUpload($thisFile)) {
            return false;
        }

        $this->fileTemp = $thisFile['tmp_name'];
        $this->fileSize = $thisFile['size'];
        $this->fileType = $thisFile['type'];
        $this->fileName = $thisFile['name'];
        $this->fileExt = $this->getExtension($this->fileName);

        if (!$this->isAllowedFileType()) {
            $this->error[] = "Недопустимое разширение.";
            return false;
        }

        if ($this->fileSize > 0) {
            $this->fileSize = round($this->fileSize/1024, 2);
        }

        if (!$this->isAllowedFileSize()) {
            $this->error[] = "Размер файла привышает допустимый.";
            return false;
        }

        return $this->fileTemp;
    }

    /**
     * Проверка загружен файл или нет
     *
     * @param $thisFile - массив даных файла
     * @return bool - true - если при загрузке не возникло ошибок, иначе - false
     */
    private function isFileUpload($thisFile)
    {
        if (!is_uploaded_file($thisFile['tmp_name'])) {
            $error = isset($thisFile['error']) ? $thisFile['error'] : 4;

            switch ($error) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $this->error[] = "Размер принятого файла превысил максимально допустимый размер.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $this->error[] = "Загружаемый файл был получен только частично.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $this->error[] = "Файл не был загружен.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $this->error[] = "Отсутствует временная папка.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $this->error[] = "Не удалось записать файл на диск.";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $this->error[] = "Не определено какое расширение остановило загрузку файла.";
                    break;
                default:
                    $this->error[] = "Не выбрано файл загрузки.";
                    break;
            }

            return false;
        }

        return true;
    }

    /**
     * Извлекает разширение файла из имени
     *
     * @param $filename - имя файла
     * @return string - разширение с точкой
     */
    private function getExtension($filename)
    {
        $x = explode('.', $filename);

        if (count($x) === 1) {
            return '';
        }

        $ext = end($x);

        return '.'.$ext;
    }

    /**
     * Валидация разширения
     *
     * @return bool - возвращает true, если загружаемій файл с допустимім разширением,
     * иначе - false
     */
    private function isAllowedFileType()
    {
        if (!in_array(substr($this->fileExt,1), self::ALLOWED_TYPE, TRUE)) {
            return false;
        }

        return true;
    }

    /**
     * Валидация максимально допустимого, для загрузки, размера файла
     *
     * @return bool - возвращает true, если размер меньше допустимого, иначе - false
     */
    private function isAllowedFileSize()
    {
        return (self::FILE_UPLOAD_MAX_SIZE === 0 || self::FILE_UPLOAD_MAX_SIZE > $this->fileSize);
    }

    /**
     * Удаляет устаревшие разпакованые архивы
     *
     * @return bool
     */
    private function deleteUnpackFile()
    {
        if ($dh = opendir(self::PATH_UNPACK_FILE)) {
            while (($file = readdir($dh)) !== false) {
                if (is_file(self::PATH_UNPACK_FILE . $file)) {
                    if (time() - filemtime(self::PATH_UNPACK_FILE . $file) > self::LIFE_TIME_UNPACK_FILE) {
                        unlink(self::PATH_UNPACK_FILE . $file);
                    }
                }
            }
        }

        closedir($dh);

        return true;
    }

}