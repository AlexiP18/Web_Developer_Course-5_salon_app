<?php
    foreach($alertas as $key => $mensajes):
        foreach($mensajes as $mensaje):
?>
    <div class="alerta <?php echo s($key); ?>">
        <?php echo s($mensaje); ?>
    </div>
<?php
        endforeach;
    endforeach;
?>
