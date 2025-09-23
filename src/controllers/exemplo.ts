/**
 * Exemplo de controllers demonstrando uso das validações
 */

import { Request, Response } from 'express';
import { PrismaClient } from '../generated/prisma';
import type { 
  ProfissionalRennerInput, 
  VisitanteInput, 
  PrestadorServicoInput 
} from '../schemas/validation';

const prisma = new PrismaClient();

/**
 * Controller para cadastro de Profissional Renner
 * Utiliza middleware de validação profissionalRennerSchema
 */
export async function criarProfissionalRenner(
  req: Request<{}, {}, ProfissionalRennerInput>, 
  res: Response
) {
  try {
    // Os dados já foram validados e normalizados pelo middleware
    const dadosValidados = req.body;

    // Criar novo profissional no banco
    const novoProfissional = await prisma.profissionalRenner.create({
      data: {
        nome: dadosValidados.nome,
        setor: dadosValidados.setor,
        dataEntrada: dadosValidados.dataEntrada,
        retorno: dadosValidados.retorno,
        observacoes: dadosValidados.observacoes
      }
    });

    res.status(201).json({
      success: true,
      message: 'Profissional Renner cadastrado com sucesso',
      data: novoProfissional
    });

  } catch (error) {
    console.error('Erro ao criar profissional:', error);
    res.status(500).json({
      error: 'Erro interno do servidor',
      message: 'Não foi possível cadastrar o profissional'
    });
  }
}

/**
 * Controller para cadastro de Visitante
 * Utiliza middleware de validação visitanteSchema
 */
export async function criarVisitante(
  req: Request<{}, {}, VisitanteInput>, 
  res: Response
) {
  try {
    // Os dados já foram validados e normalizados pelo middleware
    const dadosValidados = req.body;

    // Verificar se funcionário responsável existe
    // (Esta verificação também pode ser feita no middleware)
    
    // Criar novo visitante no banco
    const novoVisitante = await prisma.visitante.create({
      data: {
        nome: dadosValidados.nome,
        cpf: dadosValidados.cpf, // Já normalizado (apenas dígitos)
        empresa: dadosValidados.empresa,
        setor: dadosValidados.setor,
        funcionarioResponsavel: dadosValidados.funcionarioResponsavel,
        placaVeiculo: dadosValidados.placaVeiculo, // Já normalizada (uppercase)
        horaEntrada: dadosValidados.horaEntrada,
        observacoes: dadosValidados.observacoes
      }
    });

    res.status(201).json({
      success: true,
      message: 'Visitante cadastrado com sucesso',
      data: novoVisitante
    });

  } catch (error) {
    console.error('Erro ao criar visitante:', error);
    res.status(500).json({
      error: 'Erro interno do servidor',
      message: 'Não foi possível cadastrar o visitante'
    });
  }
}

/**
 * Controller para cadastro de Prestador de Serviço
 * Utiliza middleware de validação prestadorServicoSchema
 */
export async function criarPrestadorServico(
  req: Request<{}, {}, PrestadorServicoInput>, 
  res: Response
) {
  try {
    // Os dados já foram validados e normalizados pelo middleware
    const dadosValidados = req.body;

    // Criar novo prestador no banco
    const novoPrestador = await prisma.prestadorServico.create({
      data: {
        nome: dadosValidados.nome,
        cpf: dadosValidados.cpf, // Já normalizado (apenas dígitos)
        empresa: dadosValidados.empresa,
        setor: dadosValidados.setor,
        cnpj: dadosValidados.cnpj, // Já normalizado (apenas dígitos) se fornecido
        placaVeiculo: dadosValidados.placaVeiculo, // Já normalizada (uppercase)
        entrada: dadosValidados.entrada,
        observacao: dadosValidados.observacoes
      }
    });

    res.status(201).json({
      success: true,
      message: 'Prestador de serviço cadastrado com sucesso',
      data: novoPrestador
    });

  } catch (error) {
    console.error('Erro ao criar prestador:', error);
    res.status(500).json({
      error: 'Erro interno do servidor',
      message: 'Não foi possível cadastrar o prestador de serviço'
    });
  }
}

/**
 * Exemplo de endpoint para listar profissionais ativos
 */
export async function listarProfissionaisAtivos(req: Request, res: Response) {
  try {
    const profissionaisAtivos = await prisma.profissionalRenner.findMany({
      where: {
        saidaFinal: null // Ainda não saíram definitivamente
      },
      orderBy: {
        dataEntrada: 'desc'
      }
    });

    res.json({
      success: true,
      data: profissionaisAtivos,
      total: profissionaisAtivos.length
    });

  } catch (error) {
    console.error('Erro ao listar profissionais:', error);
    res.status(500).json({
      error: 'Erro interno do servidor',
      message: 'Não foi possível listar os profissionais'
    });
  }
}