/**
 * Schemas de validação usando Zod para controle de acesso
 */

import { z } from 'zod';
import { 
  isValidCPF, 
  isValidCNPJ, 
  isValidPlaca, 
  isNotFutureDateTime,
  removeNonDigits,
  normalizeString,
  normalizePlaca 
} from '../utils/validation';

// Schema base para campos comuns
const basePersonSchema = {
  nome: z.string()
    .min(3, 'Nome deve ter pelo menos 3 caracteres')
    .max(100, 'Nome não pode exceder 100 caracteres')
    .transform(normalizeString),
  
  observacoes: z.string()
    .max(500, 'Observações não podem exceder 500 caracteres')
    .optional()
    .transform(val => val ? normalizeString(val) : undefined)
};

// Schema para validação de CPF
const cpfSchema = z.string()
  .transform(removeNonDigits)
  .refine(isValidCPF, {
    message: 'CPF inválido'
  });

// Schema para validação de CNPJ
const cnpjSchema = z.string()
  .transform(removeNonDigits)
  .refine(isValidCNPJ, {
    message: 'CNPJ inválido'
  })
  .optional();

// Schema para validação de placa
const placaSchema = z.string()
  .transform(normalizePlaca)
  .refine(isValidPlaca, {
    message: 'Placa inválida. Use formato ABC1234 ou ABC1D23'
  })
  .optional();

// Schema para setor (obrigatório para colaborador e visitante)
const setorObrigatorioSchema = z.string()
  .min(2, 'Setor deve ter pelo menos 2 caracteres')
  .max(60, 'Setor não pode exceder 60 caracteres')
  .transform(normalizeString);

// Schema para setor opcional (prestador)
const setorOpcionalSchema = z.string()
  .min(2, 'Setor deve ter pelo menos 2 caracteres')
  .max(60, 'Setor não pode exceder 60 caracteres')
  .transform(normalizeString)
  .optional();

// Schema para empresa
const empresaSchema = z.string()
  .min(2, 'Empresa deve ter pelo menos 2 caracteres')
  .max(80, 'Empresa não pode exceder 80 caracteres')
  .transform(normalizeString);

/**
 * Schema para validação de Profissional Renner (Colaborador)
 */
export const profissionalRennerSchema = z.object({
  ...basePersonSchema,
  setor: setorObrigatorioSchema,
  dataEntrada: z.coerce.date()
    .refine(isNotFutureDateTime, {
      message: 'Data/hora de entrada não pode ser futura (tolerância: 5 minutos)'
    }),
  retorno: z.coerce.date().optional()
});

/**
 * Schema para validação de Visitante
 */
export const visitanteSchema = z.object({
  ...basePersonSchema,
  cpf: cpfSchema,
  empresa: empresaSchema,
  setor: setorObrigatorioSchema,
  funcionarioResponsavel: z.string()
    .min(1, 'Funcionário responsável é obrigatório')
    .max(100, 'Nome do funcionário responsável não pode exceder 100 caracteres')
    .transform(normalizeString),
  placaVeiculo: placaSchema,
  horaEntrada: z.coerce.date()
    .refine(isNotFutureDateTime, {
      message: 'Hora de entrada não pode ser futura (tolerância: 5 minutos)'
    })
});

/**
 * Schema para validação de Prestador de Serviço
 */
export const prestadorServicoSchema = z.object({
  ...basePersonSchema,
  cpf: cpfSchema,
  empresa: empresaSchema.refine(val => val.length > 0, {
    message: 'Empresa é obrigatória para prestador de serviço'
  }),
  setor: setorOpcionalSchema,
  cnpj: cnpjSchema,
  placaVeiculo: placaSchema,
  entrada: z.coerce.date()
    .refine(isNotFutureDateTime, {
      message: 'Hora de entrada não pode ser futura (tolerância: 5 minutos)'
    })
});

// Tipos derivados dos schemas
export type ProfissionalRennerInput = z.infer<typeof profissionalRennerSchema>;
export type VisitanteInput = z.infer<typeof visitanteSchema>;
export type PrestadorServicoInput = z.infer<typeof prestadorServicoSchema>;

// Schema para validação de funcionário responsável existente
export const funcionarioResponsavelSchema = z.object({
  funcionarioResponsavel: z.string()
    .min(1, 'ID do funcionário responsável é obrigatório')
    .transform(val => parseInt(val))
    .refine(val => !isNaN(val) && val > 0, {
      message: 'ID do funcionário responsável deve ser um número válido'
    })
});

export type FuncionarioResponsavelInput = z.infer<typeof funcionarioResponsavelSchema>;