<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <title>Teste - Seletor de Documento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">🧪 Teste: Seletor de Documento Internacional v2.0.0</h2>
                
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Formulário de Teste</h5>
                        
                        <form id="formTeste" class="mt-4">
                            <?php
                            $fieldName = 'teste';
                            $required = true;
                            $defaultDocType = 'CPF';
                            $defaultDocNumber = '';
                            $defaultDocCountry = 'BR';
                            require_once __DIR__ . '/components/seletor_documento.php';
                            ?>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Validar Documento
                                </button>
                            </div>
                        </form>
                        
                        <div id="resultado" class="mt-4" style="display: none;">
                            <h6>Resultado da Validação:</h6>
                            <pre id="resultadoJson" class="bg-light p-3 rounded"></pre>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">📋 Casos de Teste</h5>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Número de Teste</th>
                                    <th>País</th>
                                    <th>Esperado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>CPF</td>
                                    <td>123.456.789-09</td>
                                    <td>BR</td>
                                    <td>✅ Válido</td>
                                </tr>
                                <tr>
                                    <td>PASSAPORTE</td>
                                    <td>AB123456</td>
                                    <td>US</td>
                                    <td>✅ Válido</td>
                                </tr>
                                <tr>
                                    <td>RNE</td>
                                    <td>W1234567</td>
                                    <td>BR</td>
                                    <td>✅ Válido</td>
                                </tr>
                                <tr>
                                    <td>DNI</td>
                                    <td>12345678</td>
                                    <td>AR</td>
                                    <td>✅ Válido</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/document-validator.js"></script>
    
    <script>
    $('#formTeste').on('submit', async function(e) {
        e.preventDefault();
        
        const docType = $('#teste_doc_type').val();
        const docNumber = $('#teste_doc_number').val();
        const docCountry = $('#teste_doc_country').val() || 'BR';
        
        try {
            const result = await DocumentValidator.validate(docType, docNumber, docCountry);
            
            $('#resultadoJson').text(JSON.stringify(result, null, 2));
            $('#resultado').show();
            
            if (result.isValid) {
                $('#resultado').removeClass('alert-danger').addClass('alert alert-success');
            } else {
                $('#resultado').removeClass('alert-success').addClass('alert alert-danger');
            }
        } catch (error) {
            $('#resultadoJson').text('Erro: ' + error.message);
            $('#resultado').show().removeClass('alert-success').addClass('alert alert-danger');
        }
    });
    
    // Inicializar tooltips
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(t => new bootstrap.Tooltip(t));
    </script>
</body>
</html>
