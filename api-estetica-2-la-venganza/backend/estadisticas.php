<?php
Flight::route('GET /estadisticas', function () {

    try {
        $stmt = Flight::db()->prepare("SELECT * FROM estadisticas_mensuales ORDER BY mes DESC");
        $stmt->execute();
        Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC));
            
    } catch (PDOException $e) {
        Flight::json([
            'status' => 'error',
            'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
        ], 500);
    }
});
