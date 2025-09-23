/**
 * Testes de integração para API de validações
 */

import request from 'supertest';
import app from '../index';

describe('API Integration Tests', () => {
  
  describe('POST /api/profissionais-renner', () => {
    test('Deve aceitar dados válidos', async () => {
      const dadosValidos = {
        nome: 'João Silva',
        setor: 'Tecnologia da Informação',
        dataEntrada: new Date().toISOString(),
        observacoes: 'Novo funcionário do setor de TI'
      };

      const response = await request(app)
        .post('/api/profissionais-renner')
        .send(dadosValidos)
        .expect(201);

      expect(response.body.success).toBe(true);
      expect(response.body.message).toBe('Profissional Renner cadastrado com sucesso');
    });

    test('Deve rejeitar nome muito curto', async () => {
      const dadosInvalidos = {
        nome: 'Jo',
        setor: 'TI',
        dataEntrada: new Date().toISOString()
      };

      const response = await request(app)
        .post('/api/profissionais-renner')
        .send(dadosInvalidos)
        .expect(400);

      expect(response.body.error).toBe('Dados inválidos');
      expect(response.body.details).toEqual(
        expect.arrayContaining([
          expect.objectContaining({
            field: 'nome',
            message: 'Nome deve ter pelo menos 3 caracteres'
          })
        ])
      );
    });

    test('Deve rejeitar setor faltando', async () => {
      const dadosInvalidos = {
        nome: 'João Silva',
        dataEntrada: new Date().toISOString()
      };

      const response = await request(app)
        .post('/api/profissionais-renner')
        .send(dadosInvalidos)
        .expect(400);

      expect(response.body.details).toEqual(
        expect.arrayContaining([
          expect.objectContaining({
            field: 'setor'
          })
        ])
      );
    });
  });

  describe('POST /api/visitantes', () => {
    test('Deve aceitar dados válidos com placa', async () => {
      const dadosValidos = {
        nome: 'Maria Santos',
        cpf: '111.444.777-35',
        empresa: 'Empresa ABC LTDA',
        setor: 'Vendas',
        funcionarioResponsavel: 'João Silva',
        placaVeiculo: 'ABC-1234',
        horaEntrada: new Date().toISOString(),
        observacoes: 'Reunião de vendas trimestral'
      };

      const response = await request(app)
        .post('/api/visitantes')
        .send(dadosValidos)
        .expect(201);

      expect(response.body.success).toBe(true);
    });

    test('Deve normalizar CPF removendo máscara', async () => {
      const dadosValidos = {
        nome: 'Maria Santos',
        cpf: '111.444.777-35', // Com máscara
        empresa: 'Empresa ABC',
        setor: 'Vendas',
        funcionarioResponsavel: 'João Silva',
        horaEntrada: new Date().toISOString()
      };

      const response = await request(app)
        .post('/api/visitantes')
        .send(dadosValidos)
        .expect(201);

      // Verificar se CPF foi normalizado (sem máscara)
      expect(response.body.data.cpf).toBe('11144477735');
    });

    test('Deve normalizar placa para uppercase', async () => {
      const dadosValidos = {
        nome: 'Maria Santos',
        cpf: '11144477735',
        empresa: 'Empresa ABC',
        setor: 'Vendas',
        funcionarioResponsavel: 'João Silva',
        placaVeiculo: 'abc-1d23', // Lowercase com hífen
        horaEntrada: new Date().toISOString()
      };

      const response = await request(app)
        .post('/api/visitantes')
        .send(dadosValidos)
        .expect(201);

      // Verificar se placa foi normalizada
      expect(response.body.data.placaVeiculo).toBe('ABC1D23');
    });

    test('Deve rejeitar CPF inválido', async () => {
      const dadosInvalidos = {
        nome: 'Maria Santos',
        cpf: '111.444.777-34', // CPF inválido
        empresa: 'Empresa ABC',
        setor: 'Vendas',
        funcionarioResponsavel: 'João Silva',
        horaEntrada: new Date().toISOString()
      };

      const response = await request(app)
        .post('/api/visitantes')
        .send(dadosInvalidos)
        .expect(400);

      expect(response.body.details).toEqual(
        expect.arrayContaining([
          expect.objectContaining({
            field: 'cpf',
            message: 'CPF inválido'
          })
        ])
      );
    });

    test('Deve rejeitar placa inválida', async () => {
      const dadosInvalidos = {
        nome: 'Maria Santos',
        cpf: '11144477735',
        empresa: 'Empresa ABC',
        setor: 'Vendas',
        funcionarioResponsavel: 'João Silva',
        placaVeiculo: 'ABCD1234', // Placa inválida (4 letras)
        horaEntrada: new Date().toISOString()
      };

      const response = await request(app)
        .post('/api/visitantes')
        .send(dadosInvalidos)
        .expect(400);

      expect(response.body.details).toEqual(
        expect.arrayContaining([
          expect.objectContaining({
            field: 'placaVeiculo',
            message: 'Placa inválida. Use formato ABC1234 ou ABC1D23'
          })
        ])
      );
    });
  });

  describe('POST /api/prestadores-servico', () => {
    test('Deve aceitar dados válidos com CNPJ', async () => {
      const dadosValidos = {
        nome: 'Carlos Prestador',
        cpf: '12345678909',
        empresa: 'Prestadora de Serviços LTDA',
        setor: 'Manutenção',
        cnpj: '11.222.333/0001-81',
        placaVeiculo: 'XYZ1D23',
        entrada: new Date().toISOString(),
        observacoes: 'Manutenção preventiva dos elevadores'
      };

      const response = await request(app)
        .post('/api/prestadores-servico')
        .send(dadosValidos)
        .expect(201);

      expect(response.body.success).toBe(true);
    });

    test('Deve normalizar CNPJ removendo máscara', async () => {
      const dadosValidos = {
        nome: 'Carlos Prestador',
        cpf: '12345678909',
        empresa: 'Prestadora LTDA',
        cnpj: '11.222.333/0001-81', // Com máscara
        entrada: new Date().toISOString()
      };

      const response = await request(app)
        .post('/api/prestadores-servico')
        .send(dadosValidos)
        .expect(201);

      // Verificar se CNPJ foi normalizado
      expect(response.body.data.cnpj).toBe('11222333000181');
    });

    test('Deve rejeitar CNPJ inválido', async () => {
      const dadosInvalidos = {
        nome: 'Carlos Prestador',
        cpf: '12345678909',
        empresa: 'Prestadora LTDA',
        cnpj: '11.222.333/0001-80', // CNPJ inválido
        entrada: new Date().toISOString()
      };

      const response = await request(app)
        .post('/api/prestadores-servico')
        .send(dadosInvalidos)
        .expect(400);

      expect(response.body.details).toEqual(
        expect.arrayContaining([
          expect.objectContaining({
            field: 'cnpj',
            message: 'CNPJ inválido'
          })
        ])
      );
    });

    test('Deve aceitar setor opcional para prestador', async () => {
      const dadosValidos = {
        nome: 'Carlos Prestador',
        cpf: '12345678909',
        empresa: 'Prestadora LTDA',
        entrada: new Date().toISOString()
        // setor omitido (opcional para prestadores)
      };

      const response = await request(app)
        .post('/api/prestadores-servico')
        .send(dadosValidos)
        .expect(201);

      expect(response.body.success).toBe(true);
    });
  });

  describe('GET /api/health', () => {
    test('Deve retornar status da API', async () => {
      const response = await request(app)
        .get('/api/health')
        .expect(200);

      expect(response.body.status).toBe('OK');
      expect(response.body.message).toBe('API de Controle de Acesso funcionando');
      expect(response.body.validations).toBeDefined();
    });
  });

  describe('GET /api/schemas', () => {
    test('Deve retornar documentação dos schemas', async () => {
      const response = await request(app)
        .get('/api/schemas')
        .expect(200);

      expect(response.body.profissionalRenner).toBeDefined();
      expect(response.body.visitante).toBeDefined();
      expect(response.body.prestadorServico).toBeDefined();
    });
  });

  describe('Rota não encontrada', () => {
    test('Deve retornar 404 para rota inexistente', async () => {
      const response = await request(app)
        .get('/api/rota-inexistente')
        .expect(404);

      expect(response.body.error).toBe('Rota não encontrada');
      expect(response.body.availableRoutes).toBeDefined();
    });
  });
});