/**
 * API Express com validações de controle de acesso
 */

import express from 'express';
import cors from 'cors';
import { 
  validateBody, 
  validateFuncionarioResponsavel,
  globalValidationErrorHandler 
} from './middleware/validation';
import { 
  profissionalRennerSchema, 
  visitanteSchema, 
  prestadorServicoSchema 
} from './schemas/validation';
import {
  criarProfissionalRenner,
  criarVisitante,
  criarPrestadorServico,
  listarProfissionaisAtivos
} from './controllers/exemplo';

const app = express();
const PORT = process.env.PORT || 3000;

// Middlewares globais
app.use(cors());
app.use(express.json());

// Rotas com validação

/**
 * POST /api/profissionais-renner
 * Cria novo profissional Renner com validação completa
 */
app.post('/api/profissionais-renner', 
  validateBody(profissionalRennerSchema),
  criarProfissionalRenner
);

/**
 * POST /api/visitantes
 * Cria novo visitante com validação completa
 */
app.post('/api/visitantes',
  validateBody(visitanteSchema),
  validateFuncionarioResponsavel(), // Verifica se funcionário responsável existe
  criarVisitante
);

/**
 * POST /api/prestadores-servico
 * Cria novo prestador de serviço com validação completa
 */
app.post('/api/prestadores-servico',
  validateBody(prestadorServicoSchema),
  criarPrestadorServico
);

/**
 * GET /api/profissionais-renner
 * Lista profissionais ativos (exemplo sem validação)
 */
app.get('/api/profissionais-renner', listarProfissionaisAtivos);

/**
 * Rota de exemplo para testar a API
 */
app.get('/api/health', (req, res) => {
  res.json({
    status: 'OK',
    message: 'API de Controle de Acesso funcionando',
    timestamp: new Date().toISOString(),
    validations: {
      cpf: 'Valida dígitos verificadores e rejeita sequências repetidas',
      cnpj: 'Valida dígitos verificadores (14 dígitos)',
      placa: 'Formatos ABC1234 (antigo) e ABC1D23 (Mercosul)',
      dateTime: 'Não pode ser futura (tolerância: 5 minutos)',
      normalization: 'Remove máscaras, normaliza strings, uppercase em placas'
    }
  });
});

/**
 * Rota para documentação dos schemas (útil para desenvolvimento)
 */
app.get('/api/schemas', (req, res) => {
  res.json({
    profissionalRenner: {
      required: ['nome', 'setor', 'dataEntrada'],
      optional: ['retorno', 'observacoes'],
      validations: {
        nome: '3-100 chars, normalizado',
        setor: '2-60 chars (obrigatório)',
        observacoes: 'até 500 chars'
      }
    },
    visitante: {
      required: ['nome', 'cpf', 'empresa', 'setor', 'funcionarioResponsavel', 'horaEntrada'],
      optional: ['placaVeiculo', 'observacoes'],
      validations: {
        cpf: 'Validação completa com DV, apenas dígitos armazenados',
        funcionarioResponsavel: 'Deve existir na base de colaboradores',
        placaVeiculo: 'ABC1234 ou ABC1D23, uppercase'
      }
    },
    prestadorServico: {
      required: ['nome', 'cpf', 'empresa', 'entrada'],
      optional: ['setor', 'cnpj', 'placaVeiculo', 'observacoes'],
      validations: {
        empresa: 'Obrigatório para prestadores',
        cnpj: '14 dígitos com DV (opcional)',
        setor: 'Opcional para prestadores'
      }
    }
  });
});

// Middleware global de tratamento de erros
app.use(globalValidationErrorHandler());

// Rotas catch-all removidas para compatibilidade

// Handler global de erros
app.use((err: Error, req: express.Request, res: express.Response, next: express.NextFunction) => {
  console.error('Erro não tratado:', err);
  res.status(500).json({
    error: 'Erro interno do servidor',
    message: 'Ocorreu um erro inesperado'
  });
});

// Iniciar servidor
app.listen(PORT, () => {
  console.log(`🚀 API de Controle de Acesso iniciada em http://localhost:${PORT}`);
  console.log(`📋 Documentação dos schemas: http://localhost:${PORT}/api/schemas`);
  console.log(`💚 Health check: http://localhost:${PORT}/api/health`);
});

export default app;