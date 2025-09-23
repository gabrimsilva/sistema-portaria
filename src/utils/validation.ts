/**
 * Utilitários de validação e normalização de dados
 */

/**
 * Remove caracteres não numéricos de uma string
 */
export function removeNonDigits(value: string): string {
  return value.replace(/\D/g, '');
}

/**
 * Normaliza string removendo espaços extras e fazendo trim
 */
export function normalizeString(value: string): string {
  return value.trim().replace(/\s+/g, ' ');
}

/**
 * Converte string para uppercase
 */
export function toUpperCase(value: string): string {
  return value.toUpperCase();
}

/**
 * Valida CPF - verifica dígitos verificadores e rejeita sequências repetidas
 */
export function isValidCPF(cpf: string): boolean {
  const digits = removeNonDigits(cpf);
  
  // Deve ter exatamente 11 dígitos
  if (digits.length !== 11) return false;
  
  // Rejeita sequências repetidas (111.111.111-11, 222.222.222-22, etc.)
  if (/^(\d)\1{10}$/.test(digits)) return false;
  
  // Valida primeiro dígito verificador
  let sum = 0;
  for (let i = 0; i < 9; i++) {
    sum += parseInt(digits[i]) * (10 - i);
  }
  let remainder = sum % 11;
  let checkDigit1 = remainder < 2 ? 0 : 11 - remainder;
  
  if (parseInt(digits[9]) !== checkDigit1) return false;
  
  // Valida segundo dígito verificador
  sum = 0;
  for (let i = 0; i < 10; i++) {
    sum += parseInt(digits[i]) * (11 - i);
  }
  remainder = sum % 11;
  let checkDigit2 = remainder < 2 ? 0 : 11 - remainder;
  
  return parseInt(digits[10]) === checkDigit2;
}

/**
 * Valida CNPJ - verifica dígitos verificadores
 */
export function isValidCNPJ(cnpj: string): boolean {
  const digits = removeNonDigits(cnpj);
  
  // Deve ter exatamente 14 dígitos
  if (digits.length !== 14) return false;
  
  // Rejeita sequências repetidas
  if (/^(\d)\1{13}$/.test(digits)) return false;
  
  // Valida primeiro dígito verificador
  const weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
  let sum = 0;
  for (let i = 0; i < 12; i++) {
    sum += parseInt(digits[i]) * weights1[i];
  }
  let remainder = sum % 11;
  let checkDigit1 = remainder < 2 ? 0 : 11 - remainder;
  
  if (parseInt(digits[12]) !== checkDigit1) return false;
  
  // Valida segundo dígito verificador
  const weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
  sum = 0;
  for (let i = 0; i < 13; i++) {
    sum += parseInt(digits[i]) * weights2[i];
  }
  remainder = sum % 11;
  let checkDigit2 = remainder < 2 ? 0 : 11 - remainder;
  
  return parseInt(digits[13]) === checkDigit2;
}

/**
 * Valida placa de veículo (formato antigo ABC1234 ou Mercosul ABC1D23)
 */
export function isValidPlaca(placa: string): boolean {
  const normalized = placa.toUpperCase().replace(/[^A-Z0-9]/g, '');
  
  // Deve ter exatamente 7 caracteres
  if (normalized.length !== 7) return false;
  
  // Formato antigo: ABC1234 (3 letras + 4 números)
  const oldFormat = /^[A-Z]{3}[0-9]{4}$/;
  
  // Formato Mercosul: ABC1D23 (3 letras + 1 número + 1 letra + 2 números)
  const mercosulFormat = /^[A-Z]{3}[0-9][A-Z][0-9]{2}$/;
  
  return oldFormat.test(normalized) || mercosulFormat.test(normalized);
}

/**
 * Normaliza placa de veículo
 */
export function normalizePlaca(placa: string): string {
  return placa.toUpperCase().replace(/[^A-Z0-9]/g, '');
}

/**
 * Valida se uma data/hora não é futura (com tolerância de 5 minutos)
 */
export function isNotFutureDateTime(dateTime: Date): boolean {
  const now = new Date();
  const tolerance = 5 * 60 * 1000; // 5 minutos em millisegundos
  return dateTime.getTime() <= (now.getTime() + tolerance);
}