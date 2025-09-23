/**
 * Testes unitários para validações de campos
 */

import {
  isValidCPF,
  isValidCNPJ,
  isValidPlaca,
  normalizePlaca,
  removeNonDigits,
  normalizeString,
  isNotFutureDateTime
} from '../utils/validation';

import {
  profissionalRennerSchema,
  visitanteSchema,
  prestadorServicoSchema
} from '../schemas/validation';

describe('Validação de CPF', () => {
  test('CPF válido sem máscara', () => {
    expect(isValidCPF('11144477735')).toBe(true);
    expect(isValidCPF('12345678909')).toBe(true);
  });

  test('CPF válido com máscara', () => {
    expect(isValidCPF('111.444.777-35')).toBe(true);
    expect(isValidCPF('123.456.789-09')).toBe(true);
  });

  test('CPF inválido - dígitos verificadores incorretos', () => {
    expect(isValidCPF('11144477734')).toBe(false); // último dígito incorreto
    expect(isValidCPF('12345678900')).toBe(false); // dígitos verificadores incorretos
  });

  test('CPF inválido - sequências repetidas', () => {
    expect(isValidCPF('11111111111')).toBe(false);
    expect(isValidCPF('22222222222')).toBe(false);
    expect(isValidCPF('333.333.333-33')).toBe(false);
  });

  test('CPF inválido - formato incorreto', () => {
    expect(isValidCPF('123456789')).toBe(false); // muito curto
    expect(isValidCPF('123456789012')).toBe(false); // muito longo
    expect(isValidCPF('abcdefghijk')).toBe(false); // letras
    expect(isValidCPF('')).toBe(false); // vazio
  });
});

describe('Validação de CNPJ', () => {
  test('CNPJ válido sem máscara', () => {
    expect(isValidCNPJ('11222333000181')).toBe(true);
  });

  test('CNPJ válido com máscara', () => {
    expect(isValidCNPJ('11.222.333/0001-81')).toBe(true);
  });

  test('CNPJ inválido - dígitos verificadores incorretos', () => {
    expect(isValidCNPJ('11222333000180')).toBe(false);
  });

  test('CNPJ inválido - sequências repetidas', () => {
    expect(isValidCNPJ('11111111111111')).toBe(false);
    expect(isValidCNPJ('22.222.222/2222-22')).toBe(false);
  });

  test('CNPJ inválido - formato incorreto', () => {
    expect(isValidCNPJ('1122233300018')).toBe(false); // muito curto
    expect(isValidCNPJ('112223330001812')).toBe(false); // muito longo
    expect(isValidCNPJ('')).toBe(false); // vazio
  });
});

describe('Validação de Placa', () => {
  test('Placa válida - formato antigo', () => {
    expect(isValidPlaca('ABC1234')).toBe(true);
    expect(isValidPlaca('XYZ9876')).toBe(true);
    expect(isValidPlaca('abc1234')).toBe(true); // case insensitive
  });

  test('Placa válida - formato Mercosul', () => {
    expect(isValidPlaca('ABC1D23')).toBe(true);
    expect(isValidPlaca('XYZ2E45')).toBe(true);
    expect(isValidPlaca('abc1d23')).toBe(true); // case insensitive
  });

  test('Placa válida - com formatação', () => {
    expect(isValidPlaca('ABC-1234')).toBe(true);
    expect(isValidPlaca('ABC 1D23')).toBe(true);
    expect(isValidPlaca('ABC.1234')).toBe(true);
  });

  test('Placa inválida - formato incorreto', () => {
    expect(isValidPlaca('ABC12345')).toBe(false); // muito longa
    expect(isValidPlaca('AB1234')).toBe(false); // muito curta
    expect(isValidPlaca('1234ABC')).toBe(false); // ordem incorreta
    expect(isValidPlaca('ABCD123')).toBe(false); // 4 letras
    expect(isValidPlaca('ABC12D3')).toBe(false); // formato misto incorreto
    expect(isValidPlaca('')).toBe(false); // vazia
  });

  test('Normalização de placa', () => {
    expect(normalizePlaca('abc-1234')).toBe('ABC1234');
    expect(normalizePlaca('ABC 1D23')).toBe('ABC1D23');
    expect(normalizePlaca('abc.1d23')).toBe('ABC1D23');
  });
});

describe('Validação de Data/Hora', () => {
  test('Data/hora não futura é válida', () => {
    const agora = new Date();
    const passado = new Date(agora.getTime() - 60000); // 1 minuto atrás
    expect(isNotFutureDateTime(passado)).toBe(true);
    expect(isNotFutureDateTime(agora)).toBe(true);
  });

  test('Data/hora futura dentro da tolerância é válida', () => {
    const agora = new Date();
    const futuroProximo = new Date(agora.getTime() + 4 * 60000); // 4 minutos no futuro
    expect(isNotFutureDateTime(futuroProximo)).toBe(true);
  });

  test('Data/hora futura fora da tolerância é inválida', () => {
    const agora = new Date();
    const futuroDistante = new Date(agora.getTime() + 10 * 60000); // 10 minutos no futuro
    expect(isNotFutureDateTime(futuroDistante)).toBe(false);
  });
});

describe('Utilitários de normalização', () => {
  test('Remove dígitos não numéricos', () => {
    expect(removeNonDigits('123.456.789-09')).toBe('12345678909');
    expect(removeNonDigits('11.222.333/0001-81')).toBe('11222333000181');
    expect(removeNonDigits('ABC1234')).toBe('1234');
    expect(removeNonDigits('(11) 99999-9999')).toBe('11999999999');
  });

  test('Normaliza strings', () => {
    expect(normalizeString('  João   da  Silva  ')).toBe('João da Silva');
    expect(normalizeString('\t\nMaria\t\tSantos\n')).toBe('Maria Santos');
    expect(normalizeString('Nome Simples')).toBe('Nome Simples');
  });
});

describe('Schema de Validação - Profissional Renner', () => {
  test('Dados válidos', async () => {
    const dadosValidos = {
      nome: 'João Silva',
      setor: 'TI',
      dataEntrada: new Date('2024-01-15T09:00:00Z'),
      observacoes: 'Funcionário da TI'
    };

    const resultado = await profissionalRennerSchema.parseAsync(dadosValidos);
    expect(resultado.nome).toBe('João Silva');
    expect(resultado.setor).toBe('TI');
  });

  test('Nome muito curto', async () => {
    const dadosInvalidos = {
      nome: 'Jo',
      setor: 'TI',
      dataEntrada: new Date()
    };

    await expect(profissionalRennerSchema.parseAsync(dadosInvalidos))
      .rejects.toThrow('Nome deve ter pelo menos 3 caracteres');
  });

  test('Nome muito longo', async () => {
    const dadosInvalidos = {
      nome: 'A'.repeat(101),
      setor: 'TI',
      dataEntrada: new Date()
    };

    await expect(profissionalRennerSchema.parseAsync(dadosInvalidos))
      .rejects.toThrow('Nome não pode exceder 100 caracteres');
  });

  test('Setor muito longo', async () => {
    const dadosInvalidos = {
      nome: 'João Silva',
      setor: 'A'.repeat(61),
      dataEntrada: new Date()
    };

    await expect(profissionalRennerSchema.parseAsync(dadosInvalidos))
      .rejects.toThrow('Setor não pode exceder 60 caracteres');
  });

  test('Observações muito longas', async () => {
    const dadosInvalidos = {
      nome: 'João Silva',
      setor: 'TI',
      dataEntrada: new Date(),
      observacoes: 'A'.repeat(501)
    };

    await expect(profissionalRennerSchema.parseAsync(dadosInvalidos))
      .rejects.toThrow('Observações não podem exceder 500 caracteres');
  });
});

describe('Schema de Validação - Visitante', () => {
  test('Dados válidos', async () => {
    const dadosValidos = {
      nome: 'Maria Santos',
      cpf: '111.444.777-35',
      empresa: 'Empresa XYZ',
      setor: 'Vendas',
      funcionarioResponsavel: 'João Silva',
      placaVeiculo: 'ABC1234',
      horaEntrada: new Date(),
      observacoes: 'Reunião de vendas'
    };

    const resultado = await visitanteSchema.parseAsync(dadosValidos);
    expect(resultado.cpf).toBe('11144477735'); // CPF normalizado
    expect(resultado.placaVeiculo).toBe('ABC1234');
  });

  test('CPF inválido', async () => {
    const dadosInvalidos = {
      nome: 'Maria Santos',
      cpf: '111.444.777-34', // CPF inválido
      empresa: 'Empresa XYZ',
      setor: 'Vendas',
      funcionarioResponsavel: 'João Silva',
      horaEntrada: new Date()
    };

    await expect(visitanteSchema.parseAsync(dadosInvalidos))
      .rejects.toThrow('CPF inválido');
  });

  test('Placa inválida', async () => {
    const dadosInvalidos = {
      nome: 'Maria Santos',
      cpf: '111.444.777-35',
      empresa: 'Empresa XYZ',
      setor: 'Vendas',
      funcionarioResponsavel: 'João Silva',
      placaVeiculo: 'ABCD1234', // Placa inválida
      horaEntrada: new Date()
    };

    await expect(visitanteSchema.parseAsync(dadosInvalidos))
      .rejects.toThrow('Placa inválida');
  });
});

describe('Schema de Validação - Prestador de Serviço', () => {
  test('Dados válidos com CNPJ', async () => {
    const dadosValidos = {
      nome: 'Carlos Prestador',
      cpf: '12345678909',
      empresa: 'Prestadora LTDA',
      cnpj: '11.222.333/0001-81',
      placaVeiculo: 'XYZ1D23',
      entrada: new Date(),
      observacoes: 'Manutenção elevador'
    };

    const resultado = await prestadorServicoSchema.parseAsync(dadosValidos);
    expect(resultado.cnpj).toBe('11222333000181'); // CNPJ normalizado
    expect(resultado.placaVeiculo).toBe('XYZ1D23');
  });

  test('CNPJ inválido', async () => {
    const dadosInvalidos = {
      nome: 'Carlos Prestador',
      cpf: '12345678909',
      empresa: 'Prestadora LTDA',
      cnpj: '11.222.333/0001-80', // CNPJ inválido
      entrada: new Date()
    };

    await expect(prestadorServicoSchema.parseAsync(dadosInvalidos))
      .rejects.toThrow('CNPJ inválido');
  });

  test('Empresa obrigatória para prestador', async () => {
    const dadosInvalidos = {
      nome: 'Carlos Prestador',
      cpf: '12345678909',
      empresa: 'A', // Empresa muito curta (deve falhar na validação mínima)
      entrada: new Date()
    };

    await expect(prestadorServicoSchema.parseAsync(dadosInvalidos))
      .rejects.toThrow('Empresa deve ter pelo menos 2 caracteres');
  });
});