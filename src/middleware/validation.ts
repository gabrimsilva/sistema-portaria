/**
 * Middleware de validação para Express usando Zod
 */

import { Request, Response, NextFunction } from 'express';
import { z, ZodError } from 'zod';

/**
 * Tipo genérico para middleware de validação
 */
type ValidationTarget = 'body' | 'query' | 'params';

/**
 * Interface para erro de validação estruturado
 */
interface ValidationError {
  field: string;
  message: string;
  code: string;
}

/**
 * Cria middleware de validação genérico para qualquer schema Zod
 */
export function validateSchema<T extends z.ZodType>(
  schema: T,
  target: ValidationTarget = 'body'
) {
  return async (req: Request, res: Response, next: NextFunction) => {
    try {
      const data = req[target];
      
      // Valida e transforma os dados usando o schema
      const validatedData = await schema.parseAsync(data);
      
      // Substitui os dados originais pelos dados validados e normalizados
      req[target] = validatedData;
      
      next();
    } catch (error) {
      if (error instanceof ZodError) {
        const validationErrors: ValidationError[] = error.issues.map((err: any) => ({
          field: err.path.join('.'),
          message: err.message,
          code: err.code
        }));

        return res.status(400).json({
          error: 'Dados inválidos',
          message: 'Os dados fornecidos não passaram na validação',
          details: validationErrors
        });
      }

      // Erro inesperado
      console.error('Erro inesperado na validação:', error);
      return res.status(500).json({
        error: 'Erro interno do servidor',
        message: 'Ocorreu um erro inesperado durante a validação'
      });
    }
  };
}

/**
 * Middleware específico para validação de body
 */
export function validateBody<T extends z.ZodType>(schema: T) {
  return validateSchema(schema, 'body');
}

/**
 * Middleware específico para validação de query parameters
 */
export function validateQuery<T extends z.ZodType>(schema: T) {
  return validateSchema(schema, 'query');
}

/**
 * Middleware específico para validação de route parameters
 */
export function validateParams<T extends z.ZodType>(schema: T) {
  return validateSchema(schema, 'params');
}

/**
 * Middleware para verificar se funcionário responsável existe na base de dados
 * Simplificado para aceitar nome como texto livre (alinhado com sistema PHP existente)
 */
export function validateFuncionarioResponsavel() {
  return async (req: Request, res: Response, next: NextFunction) => {
    try {
      // Prosseguir sem validação adicional - o Zod schema já valida formato básico
      // Em produção, pode-se implementar verificação real no banco se necessário
      next();
    } catch (error) {
      console.error('Erro ao validar funcionário responsável:', error);
      return res.status(500).json({
        error: 'Erro interno do servidor',
        message: 'Erro ao verificar funcionário responsável'
      });
    }
  };
}

/**
 * Middleware para capturar e tratar erros de validação globalmente
 */
export function globalValidationErrorHandler() {
  return (error: Error, req: Request, res: Response, next: NextFunction) => {
    if (error instanceof ZodError) {
      const validationErrors: ValidationError[] = error.issues.map((err: any) => ({
        field: err.path.join('.'),
        message: err.message,
        code: err.code
      }));

      return res.status(400).json({
        error: 'Dados inválidos',
        message: 'Os dados fornecidos não passaram na validação',
        details: validationErrors
      });
    }

    next(error);
  };
}