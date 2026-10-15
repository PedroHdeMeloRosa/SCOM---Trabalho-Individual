# Documentação da API - SCOM War Thunder

Esta documentação descreve os principais endpoints RESTful utilizados na comunicação entre o Frontend (JavaScript) e o Backend (PHP).

---

## 1. Autenticação (Login)
Valida as credenciais do usuário, emite o token CSRF e aplica rate limiting.

* **URL:** `/php/api/auth.php`
* **Método HTTP:** `POST`
* **Parâmetros Body (FormData):**
  * `email` (string, obrigatório)
  * `senha` (string, obrigatório)
* **Respostas Esperadas:**
  * **200 OK (Sucesso)**
    ```json
    {
      "status": "success",
      "message": "Acesso autorizado, Comandante.",
      "perfil": "admin",
      "nickname": "GhostSniper",
      "csrf_token": "a1b2c3d4e5f6..."
    }
    ```
  * **401 Unauthorized (Erro de Credencial)**
    ```json
    {
      "status": "error",
      "message": "Credenciais inválidas. Restam 3 tentativa(s)."
    }
    ```
  * **403 Forbidden (Bloqueio por Força Bruta)**
    ```json
    {
      "status": "blocked",
      "minutos_restantes": 15
    }
    ```

---

## 2. Alistamento (Registro)
Cria um novo soldado no banco de dados com senha criptografada e sanitização XSS.

* **URL:** `/php/api/register.php`
* **Método HTTP:** `POST`
* **Parâmetros Body (FormData):**
  * `nome` (string, obrigatório)
  * `sobrenome` (string, obrigatório)
  * `nickname` (string, obrigatório)
  * `email` (string, obrigatório)
  * `senha` (string, obrigatório)
* **Respostas Esperadas:**
  * **201 Created (Sucesso)**
    ```json
    {"status": "success", "message": "Alistamento concluído com sucesso."}
    ```
  * **409 Conflict (E-mail ou Nick já existente)**
    ```json
    {"status": "error", "message": "Este E-mail ou Nickname já está em uso nas nossas fileiras."}
    ```

---

## 3. Consulta de Dossiê (Ficha Tática)
Retorna todos os dados dinâmicos, lore e imagens de um blindado específico.

* **URL:** `/php/api/ficha_api.php?id={ID}`
* **Método HTTP:** `GET`
* **Parâmetros URL:**
  * `id` (int, obrigatório): ID do tanque.
* **Respostas Esperadas:**
  * **200 OK (Sucesso)**
    ```json
    {
      "status": "success",
      "dados": {
        "id": 1,
        "nome": "M4A3E2 Sherman Jumbo",
        "nivel": "II",
        "armamento": "Canhão M3 de 75 mm",
        "nacao_nome": "E.U.A."
      }
    }
    ```
  * **404 Not Found (Veículo Inexistente)**
    ```json
    {"status": "error", "message": "Blindado não encontrado nos registros."}
    ```

---

## 4. Gestão de Nações (Alto Comando)
Endpoint restrito a administradores. Cria ou atualiza uma nação. É protegido por CSRF.

* **URL:** `/php/api/admin_index_api.php`
* **Método HTTP:** `POST`
* **Parâmetros Body (FormData):**
  * `acao` (string, obrigatório): Valor `salvar_nacao`.
  * `nome` (string, obrigatório)
  * `cor` (string, opcional)
  * `csrf_token` (string, obrigatório)
* **Respostas Esperadas:**
  * **200 OK (Sucesso)**
    ```json
    {"status": "success", "message": "Nova Nação Fundada!"}
    ```
  * **403 Forbidden (Sem token CSRF ou Não é Admin)**
    ```json
    {"status": "error", "message": "Falha de segurança: Token CSRF inválido ou ausente."}
    ```