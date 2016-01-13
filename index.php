<!DOCTYPE html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>Обработка excel файла</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php
        $arrData = array();
        $arrTitle = array();
        $er = array();

        if (isset($_POST) && array_key_exists('getData', $_POST)) {
            require_once('/modules/parseFile.php');

            $file = new parseFile();
            $xmlObj = $file->getXMLObj('uploadFile');
            $arrData = $file->getDataArray();
            $arrTitle = $file->getTitleArray();
            $er = $file->error;
        }
    ?>
    <section class="container">
        <div class="article_add">

            <form method="post" action="/" class="main" ENCTYPE="multipart/form-data">
                <?php
                if ($er && is_array($er) && count($er)) { ?>
                    <p class="error">
                        <?php foreach ($er as $error) {
                            echo $error . '<br>';
                        }
                        ?>
                    </p>
                <?php } ?>
                <p><input type="file" name="uploadFile" value=""></p>
                <p class="submit"><input type="submit" name="getData" value="Получить данные"></p>
            </form>

            <?php
                if (isset($_POST) && array_key_exists('getData', $_POST)) {
                    require_once('/modules/parseFile.php');

                    $file = new parseFile();
                    $xmlObj = $file->getXMLObj('uploadFile');
                    $arrData = $file->getDataArray();
                    $arrTitle = $file->getTitleArray();
                    $er = $file->error;
                }
            ?>
            <?php
                if ($arrTitle && is_array($arrTitle) && count($arrTitle) > 0) {
                    foreach ($arrTitle as $listNum => $titles) {
                        echo '<table>';

                        echo "<tr>";
                        foreach ($titles as $columnNum => $title) {
                            echo "<td>$title</td>";
                        }
                        echo "</tr>";

                        if ($arrData && is_array($arrData) && count($arrData) > 0) {
                            foreach ($arrData[$listNum] as $rowNum => $row) {
                                echo "<tr>";
                                foreach ($row as $cell) {
                                    echo "<td>$cell</td>";
                                }
                                echo "</tr>";
                            }
                        }
                        echo '</table><br><br>';
                    }
                }
            ?>

        </div>
    </section>
</body>
</html>