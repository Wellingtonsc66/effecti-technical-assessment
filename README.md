# Effecti Technical Assessment

Aplicação de gestão de clientes, serviços e contratos, com backend em Laravel e interface administrativa em Vue 3.

O projeto entrega:

- CRUD de clientes
- CRUD de serviços
- CRUD de contratos
- Inclusão e remoção de itens de serviço dentro de contratos
- Cálculo de subtotal, ajustes e total mensal do contrato
- Histórico de alterações por contrato
- Interface única para operação manual da avaliação

## Stack utilizada

### Backend

- PHP 8.5 no projeto e PHP-FPM 8.5 no ambiente Docker
- Laravel 13
- MySQL
- Eloquent ORM
- PHPUnit para testes de feature
- Laravel Pint para padronização de código

### Frontend

- Vue 3
- Vite
- Axios
- PrimeVue 4
- Tailwind CSS 4
- Tema Aura, via pacote `@primeuix/themes`

## Interface web

A rota raiz `/` carrega uma SPA simples para demonstrar o fluxo completo da avaliação.

Essa interface foi construída em Vue 3 e organizada em três abas:

- Clientes
- Serviços
- Contratos

### O que a interface permite

- cadastrar, editar e excluir clientes
- cadastrar, editar e excluir serviços
- cadastrar, editar e excluir contratos
- adicionar serviços a contratos com quantidade e valor unitário
- usar automaticamente o valor base do serviço ao vincular um item ao contrato
- remover itens de um contrato
- visualizar subtotal, descontos e total mensal
- visualizar o histórico de alterações do contrato
- exibir mensagens de sucesso, aviso e erro na própria tela

### Tema utilizado no Vue

A interface usa PrimeVue com o preset Aura:

- biblioteca visual: PrimeVue
- preset de tema: Aura
- apoio de layout e utilitários: Tailwind CSS 4
- linguagem visual aplicada na tela: base em tons slate com destaque em emerald

Resumo do direcionamento visual:

- cards para separar blocos de formulário e listagem
- contraste focado no tema claro da interface
- tabelas responsivas com rolagem horizontal quando necessário
- feedback visual com componentes do PrimeVue, como Button, Card, Message e Tag

## API disponível

Todos os endpoints ficam sob o prefixo `/api`.

### Clientes

- `GET /api/clients`
- `POST /api/clients`
- `GET /api/clients/{client}`
- `PUT /api/clients/{client}`
- `DELETE /api/clients/{client}`

### Serviços

- `GET /api/services`
- `POST /api/services`
- `GET /api/services/{service}`
- `PUT /api/services/{service}`
- `DELETE /api/services/{service}`

### Contratos

- `GET /api/contracts`
- `POST /api/contracts`
- `GET /api/contracts/{contract}`
- `PUT /api/contracts/{contract}`
- `DELETE /api/contracts/{contract}`
- `POST /api/contracts/{contract}/items`
- `DELETE /api/contracts/{contract}/items/{contractItem}`

## Estrutura funcional implementada

### Clientes

Cada cliente possui:

- nome
- CPF ou CNPJ
- e-mail
- status

Validações implementadas:

- documento obrigatório
- validação real de CPF/CNPJ por regra customizada
- unicidade de documento
- e-mail obrigatório com validação RFC
- unicidade de e-mail
- status validado por enum

### Serviços

Cada serviço possui:

- nome
- valor base mensal

Validações implementadas:

- nome obrigatório
- valor obrigatório
- suporte a edição pela interface com máscara monetária no frontend

### Contratos

Cada contrato possui:

- cliente vinculado
- data de início
- data de término opcional
- status
- itens de contrato
- histórico de alterações
- bloco de pricing agregado no retorno da API

O payload retornado inclui:

- dados do cliente
- itens do contrato com total da linha
- histórico de mudanças
- pricing com subtotal, adjustments e total

## Regra de negócio implementada

O item aberto da avaliação foi tratado com uma regra de precificação extensível.

### Regra escolhida

Foi implementado desconto por quantidade:

- quando um item do contrato possui quantidade maior ou igual a 5
- é aplicado desconto de 10% sobre o total daquele item
- o desconto é retornado em `pricing.adjustments`
- o valor final mensal do contrato considera subtotal menos os descontos aplicáveis

Exemplo:

- item A: 2 x 100 = 200
- item B: 5 x 200 = 1000
- subtotal = 1200
- desconto do item B = 100
- total = 1100

### Outra regra de domínio implementada

Contratos cancelados não podem ser editados.

Essa restrição vale para:

- atualização do contrato
- inclusão de novos itens
- remoção de itens existentes

Na interface, contratos cancelados também têm ações de edição bloqueadas e mensagem de aviso para o usuário.

## Histórico de alterações

Toda operação relevante de contrato registra histórico:

- criação do contrato
- atualização do contrato
- adição de serviço ao contrato
- remoção de serviço do contrato

Esse histórico é persistido e retornado pela API para consulta na interface.

## Organização da lógica

### Camada de serviço

As regras principais foram centralizadas em serviços para evitar lógica de negócio espalhada em controllers:

- `ContractManagementService`: criação, edição, inclusão e remoção de itens, além de auditoria e bloqueio de edição em contrato cancelado
- `ContractPricingService`: cálculo de subtotal, aplicação de ajustes e composição do total final
- `QuantityDiscountPricingRule`: regra concreta de desconto por quantidade

Essa abordagem deixa a regra aberta preparada para novas estratégias de pricing no futuro.

## Testes automatizados

Foram implementados testes de feature cobrindo cenários centrais da avaliação:

- rejeição de CPF inválido
- rejeição de e-mail sem domínio completo
- cálculo do total do contrato com desconto por quantidade
- bloqueio de edição em contrato cancelado

Para executar:

```bash
docker compose exec app php artisan test
```

## Ambiente com Docker

O projeto inclui ambiente Docker com:

- app PHP-FPM
- Nginx
- MySQL
- Xdebug

Subida do ambiente:

```bash
docker compose up -d --build
```

Execução das migrations:

```bash
docker compose exec app php artisan migrate
```

Aplicação disponível em:

- `http://localhost:8003`

## Build de assets

```bash
docker compose exec app npm run build
```

## Padronização de código

O projeto usa Laravel Pint.

Também foi adicionada configuração de workspace do VS Code para usar o Pint como formatter de arquivos PHP ao salvar, via `.vscode/settings.json`.

Execução manual:

```bash
docker compose exec app ./vendor/bin/pint
```

## Observações para avaliação

Os principais pontos entregues nesta solução foram:

- separação entre API e interface web
- uso de enums e requests para validação do domínio
- regra de negócio aberta tratada por serviço de pricing extensível
- persistência de histórico de mudanças em contratos
- interface única em Vue para operar todos os fluxos
- interface com PrimeVue Aura e Tailwind CSS
- cobertura básica de testes para os cenários críticos
