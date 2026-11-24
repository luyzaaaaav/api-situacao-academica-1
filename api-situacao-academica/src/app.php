<?php
declare(strict_types=1);

$json = static function (array $data, int $status = 200, array $headers = []): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    foreach ($headers as $name => $value) {
        header($name . ': ' . $value);
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

$readJson = static function () use ($json): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
        $json(['erro' => 'Envie os dados em um JSON válido.'], 400);
    }
    return $data;
};

$storageDir = dirname(__DIR__) . '/storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0775, true);
}

$pdo = new PDO('sqlite:' . $storageDir . '/situacao.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS situacoes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        unidadeCurricular TEXT NOT NULL,
        aprovado INTEGER NOT NULL CHECK (aprovado IN (0, 1))
    )'
);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/') ?: '/';

// POST /situacao - cadastra a situação.
if ($path === '/situacao' && $method === 'POST') {
    $data = $readJson();
    $required = ['nome', 'unidadeCurricular', 'aprovado'];
    foreach ($required as $field) {
        if (!array_key_exists($field, $data)) {
            $json(['erro' => "O campo '{$field}' é obrigatório."], 400);
        }
    }

    if (!is_string($data['nome']) || trim($data['nome']) === '' ||
        !is_string($data['unidadeCurricular']) || trim($data['unidadeCurricular']) === '' ||
        !is_bool($data['aprovado'])) {
        $json([
            'erro' => 'Preencha nome e unidadeCurricular com texto e use true ou false no campo aprovado.'
        ], 400);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO situacoes (nome, unidadeCurricular, aprovado) VALUES (:nome, :unidade, :aprovado)'
    );
    $stmt->execute([
        ':nome' => trim($data['nome']),
        ':unidade' => trim($data['unidadeCurricular']),
        ':aprovado' => $data['aprovado'] ? 1 : 0,
    ]);

    $id = (int) $pdo->lastInsertId();
    $json([
        'id' => $id,
        'nome' => trim($data['nome']),
        'unidadeCurricular' => trim($data['unidadeCurricular']),
        'aprovado' => $data['aprovado'],
    ], 201, ['Location' => '/situacao/' . $id]);
}

// As rotas com identificador seguem o padrão /situacao/{id}.
if (preg_match('#^/situacao/(\d+)$#', $path, $matches)) {
    $id = (int) $matches[1];

    if ($method === 'GET') {
        $stmt = $pdo->prepare('SELECT id, nome, unidadeCurricular, aprovado FROM situacoes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $registro = $stmt->fetch();

        if (!$registro) {
            $json(['erro' => 'Não encontrei essa situação.'], 404);
        }

        $registro['id'] = (int) $registro['id'];
        $registro['aprovado'] = (bool) $registro['aprovado'];
        $json($registro);
    }

    if ($method === 'PUT') {
        $data = $readJson();
        if (array_keys($data) !== ['aprovado'] || !is_bool($data['aprovado'])) {
            $json([
                'erro' => 'Mande só o campo aprovado e use true ou false.'
            ], 400);
        }

        $stmt = $pdo->prepare('UPDATE situacoes SET aprovado = :aprovado WHERE id = :id');
        $stmt->execute([':aprovado' => $data['aprovado'] ? 1 : 0, ':id' => $id]);

        if ($stmt->rowCount() === 0) {
            // Diferencia registro inexistente de atualização com o mesmo valor.
            $check = $pdo->prepare('SELECT id FROM situacoes WHERE id = :id');
            $check->execute([':id' => $id]);
            if (!$check->fetch()) {
                $json(['erro' => 'Não encontrei essa situação.'], 404);
            }
        }

        $json(['mensagem' => 'Situação atualizada!']);
    }

    if ($method === 'DELETE') {
        $stmt = $pdo->prepare('DELETE FROM situacoes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        if ($stmt->rowCount() === 0) {
            $json(['erro' => 'Não encontrei essa situação.'], 404);
        }
        $json(['mensagem' => 'Situação removida!']);
    }

    $json(['erro' => 'Esse método não pode ser usado nessa rota.'], 405, ['Allow' => 'GET, PUT, DELETE']);
}

if ($path === '/situacao') {
    $json(['erro' => 'Esse método não pode ser usado nessa rota.'], 405, ['Allow' => 'POST']);
}

$json(['erro' => 'Essa rota não existe.'], 404);
