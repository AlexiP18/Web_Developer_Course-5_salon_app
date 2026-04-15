<?php

namespace Controllers;

use Model\Cita;
use Model\CitaServicio;
use Model\Servicio;

class APIController {
    public static function index() {
        $servicios = Servicio::all();
        echo json_encode($servicios);
    }

    public static function guardar() {
        session_start();
        isAuth();

        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['resultado' => false, 'mensaje' => 'Método no permitido']);
            return;
        }

        $fecha = $_POST['fecha'] ?? '';
        $hora = $_POST['hora'] ?? '';
        $servicios = $_POST['servicios'] ?? '';

        if(!$fecha || !$hora || !$servicios) {
            http_response_code(400);
            echo json_encode(['resultado' => false, 'mensaje' => 'Datos incompletos']);
            return;
        }

        // Almacena la Cita y devuelve el ID
        $cita = new Cita([
            'fecha' => $fecha,
            'hora' => $hora,
            'usuarioId' => $_SESSION['id']
        ]);
        $resultado = $cita->guardar();

        $id = $resultado['id'];

        // Almacena la Cita y el Servicio

        // Almacena los Servicios con el ID de la Cita
        $idServicios = array_filter(array_map('intval', explode(",", $servicios)));
        if(empty($idServicios)) {
            http_response_code(400);
            echo json_encode(['resultado' => false, 'mensaje' => 'Servicios no válidos']);
            return;
        }

        foreach($idServicios as $idServicio) {
            $args = [
                'citaId' => $id,
                'servicioId' => $idServicio
            ];
            $citaServicio = new CitaServicio($args);
            $citaServicio->guardar();
        }

        echo json_encode(['resultado' => $resultado]);
    }

    public static function eliminar() {
        session_start();
        isAdmin();

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if(!$id) {
                header('Location: /admin');
                exit;
            }

            $cita = Cita::find($id);
            if($cita) {
                $cita->eliminar();
            }

            $referer = $_SERVER['HTTP_REFERER'] ?? '/admin';
            header('Location:' . $referer);
            exit;
        }
    }
}
