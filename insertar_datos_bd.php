<?php
$id_sentiment_queue = $_POST['id_sentiment_queue'];
$fecha_insert_sentiment_queue = $_POST['fecha_insert_sentiment_queue'];
$fecha_update_sentiment_queue = $_POST['fecha_update_sentiment_queue'];
$texto_evaluacion_sentiment_queue = $_POST['texto_evaluacion_sentiment_queue'];
$puntaje_sentiment_queue = $_POST['puntaje_sentiment_queue'];
$estado_id_sentiment_queue = $_POST['estado_id_sentiment_queue'];
$descripcion_error_sentiment_queue = $_POST ['descripcion_error_sentiment_queue'];

$conexion = new SQLite3 ("Proyecto.sql")

$conexion-> exec ("CREATE TABLE sentiment_queue (id_sentiment_queue INTEGER, fecha_insert_sentiment_queue DATE, texto_evaluacion_sentiment_queue TEXT, puntaje_sentiment_queue INTEGER, estado_id_sentiment_queue INTEGER, descripcion_error_sentiment_queue VARCHAR (255) )");

$conexion-> exec ("INSERT INTO sentiment_queue VALUES ('".$id_sentiment_queue."', '".$fecha_insert_sentiment_queue."','".$texto_evaluacion_sentiment_queue."','".$texto_evaluacion_sentiment_queue."','"$puntaje_sentiment_queue"', '".$estado_id_sentiment_queue."', '".$descripcion_error_sentiment_queue."' ) ")

$consulta = $conexion->query ("SELECT * FROM sentiment_queue");

while ($fila = $consulta -> fetchArray()){
    echo $fila ['id_sentiment_queue'];
    echo $fila ['fecha_insert_sentiment_queue'];
    echo $fila ['texto_evaluacion_sentiment_queue'];
    echo $fila ['puntaje_sentiment_queue'];
    echo $fila ['estado_id_sentiment_queue'];
    echo $fila ['descripcion_error_sentiment_queue'];
}

?>