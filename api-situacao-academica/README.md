# API de situação acadêmica

**Alunos:** [adicione aqui os nomes dos integrantes]

Esse projeto foi feito em PHP para cadastrar e consultar a situação dos alunos nas unidades curriculares. Também dá para atualizar se o aluno foi aprovado e apagar um registro.

## O que foi usado

- PHP 8.1 ou superior
- SQLite (o banco é criado quando a API é usada pela primeira vez)
- JSON para enviar e receber os dados

## Como rodar

1. Instale o PHP 8.1 ou mais recente e habilite a extensão PDO SQLite (`pdo_sqlite`).
2. Abra o terminal dentro da pasta do projeto.
3. Rode este comando:

   ```bash
   php -S localhost:8000 -t public public/index.php
   ```

4. A API vai ficar disponível em `http://localhost:8000`. Na primeira requisição, o projeto cria o banco `storage/situacao.sqlite` e a tabela.

## Rotas

Quando enviar dados, use o cabeçalho `Content-Type: application/json`.

### 1. Cadastrar situação acadêmica

- **Método e rota:** `POST /situacao`
- **Corpo JSON:**

  ```json
  {
    "nome": "Maria da Silva",
    "unidadeCurricular": "Desenvolvimento Web",
    "aprovado": true
  }
  ```

- **Resposta:** `201 Created`, com os dados cadastrados e o `id`.
- **Erro:** `400 Bad Request` se os dados estiverem errados.

### 2. Consultar situação acadêmica

- **Método e rota:** `GET /situacao/{id}`
- **Exemplo:** `GET /situacao/1`
- **Resposta:** `200 OK`, com `id`, `nome`, `unidadeCurricular` e `aprovado`.
- **Erro:** `404 Not Found` se não existir uma situação com esse id.

### 3. Atualizar situação acadêmica

- **Método e rota:** `PUT /situacao/{id}`
- **Exemplo:** `PUT /situacao/1`
- **Corpo JSON (mande só esse campo):**

  ```json
  {
    "aprovado": false
  }
  ```

- **Resposta:** `200 OK`.
- **Erros:** `400 Bad Request` se mandar outros campos ou algo diferente de `true` ou `false`; `404 Not Found` se não existir uma situação com esse id.

### 4. Remover situação acadêmica

- **Método e rota:** `DELETE /situacao/{id}`
- **Exemplo:** `DELETE /situacao/1`
- **Resposta:** `200 OK`, avisando que a situação foi removida.
- **Erro:** `404 Not Found` se não existir uma situação com esse id. Depois de apagar, não dá mais para consultar o registro.

## Códigos HTTP utilizados

| Código | Significado |
|---|---|
| `200` | Deu certo |
| `201` | Registro criado |
| `400` | Dados inválidos |
| `404` | Rota ou registro não encontrado |
| `405` | Método não permitido nessa rota |

## Testando as rotas

Com o servidor rodando, abra outro terminal e execute os comandos abaixo usando `curl`.

### POST — cadastrar

```bash
curl -i -X POST http://localhost:8000/situacao \
  -H "Content-Type: application/json" \
  -d '{"nome":"Maria da Silva","unidadeCurricular":"Desenvolvimento Web","aprovado":true}'
```

Anote o `id` que aparecer na resposta. Nos próximos exemplos, vamos considerar que seja `1`. A resposta deve ser `201 Created` com os dados cadastrados.

### GET — consultar

```bash
curl -i http://localhost:8000/situacao/1
```

Deve retornar `200 OK` com os dados da situação.

### PUT — mudar se foi aprovado

```bash
curl -i -X PUT http://localhost:8000/situacao/1 \
  -H "Content-Type: application/json" \
  -d '{"aprovado":false}'
```

Deve retornar `200 OK` avisando que a situação foi atualizada.

### DELETE — remover

```bash
curl -i -X DELETE http://localhost:8000/situacao/1
```

Deve retornar `200 OK` avisando que a situação foi removida.

### Conferir se apagou

```bash
curl -i http://localhost:8000/situacao/1
```

Deve retornar `404 Not Found`, porque o registro já foi apagado.

## Estrutura do projeto

```text
api-situacao-academica/
├── public/
│   └── index.php       # Ponto de entrada da API
├── src/
│   ├── app.php         # Rotas e regras da API
├── storage/            # Banco SQLite criado ao executar
└── README.md           # Documentação e testes
```

## Para entregar no GitHub

1. Crie um repositório público no GitHub.
2. Coloque os nomes do grupo no começo deste README.
3. Envie os arquivos do projeto para o repositório.
4. Entregue o link do repositório.

**Importante:** tire prints dos testes de `POST`, `GET`, `PUT` e `DELETE` enquanto estiver rodando a API e coloque na entrega. Os comandos estão acima; os prints ainda precisam ser feitos.
