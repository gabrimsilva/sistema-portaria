--
-- PostgreSQL database dump
--

-- Dumped from database version 16.9 (63f4182)
-- Dumped by pg_dump version 16.9

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: update_updated_at_column(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.update_updated_at_column() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: acessos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.acessos (
    id integer NOT NULL,
    tipo character varying(10) NOT NULL,
    funcionario_id integer,
    visitante_id integer,
    usuario_id integer,
    observacoes text,
    data_hora timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT acessos_tipo_check CHECK (((tipo)::text = ANY ((ARRAY['entrada'::character varying, 'saida'::character varying])::text[]))),
    CONSTRAINT check_pessoa CHECK ((((funcionario_id IS NOT NULL) AND (visitante_id IS NULL)) OR ((funcionario_id IS NULL) AND (visitante_id IS NOT NULL))))
);


--
-- Name: acessos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.acessos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: acessos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.acessos_id_seq OWNED BY public.acessos.id;


--
-- Name: audit_log; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.audit_log (
    id integer NOT NULL,
    user_id integer,
    acao character varying(50) NOT NULL,
    entidade character varying(50) NOT NULL,
    entidade_id integer NOT NULL,
    dados_antes jsonb,
    dados_depois jsonb,
    ip_address character varying(45),
    user_agent text,
    "timestamp" timestamp with time zone DEFAULT (now() AT TIME ZONE 'UTC'::text),
    severidade character varying(20) DEFAULT 'INFO'::character varying NOT NULL,
    modulo character varying(50) DEFAULT 'sistema'::character varying NOT NULL,
    resultado character varying(20) DEFAULT 'success'::character varying NOT NULL
);


--
-- Name: audit_log_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.audit_log_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: audit_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.audit_log_id_seq OWNED BY public.audit_log.id;


--
-- Name: auth_policies; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.auth_policies (
    id integer NOT NULL,
    password_min_length integer DEFAULT 8,
    password_expiry_days integer DEFAULT 90,
    session_timeout_minutes integer DEFAULT 1440,
    require_2fa boolean DEFAULT false,
    enable_sso boolean DEFAULT false,
    sso_provider character varying(50),
    sso_client_id character varying(255),
    sso_issuer character varying(255),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT auth_policies_password_expiry_days_check CHECK ((password_expiry_days > 0)),
    CONSTRAINT auth_policies_password_min_length_check CHECK ((password_min_length >= 4)),
    CONSTRAINT auth_policies_session_timeout_minutes_check CHECK ((session_timeout_minutes > 0))
);


--
-- Name: auth_policies_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.auth_policies_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: auth_policies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.auth_policies_id_seq OWNED BY public.auth_policies.id;


--
-- Name: biometric_files; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.biometric_files (
    id integer NOT NULL,
    entity_type character varying(50) NOT NULL,
    entity_id integer NOT NULL,
    secure_filename character varying(255) NOT NULL,
    original_filename character varying(255),
    mime_type character varying(100) DEFAULT 'image/jpeg'::character varying,
    file_size_bytes integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    created_by integer,
    accessed_at timestamp without time zone,
    deleted_at timestamp without time zone,
    deletion_reason text,
    CONSTRAINT biometric_files_entity_type_check CHECK (((entity_type)::text = ANY ((ARRAY['employees'::character varying, 'visitors'::character varying, 'prestadores'::character varying])::text[])))
);


--
-- Name: biometric_files_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.biometric_files_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: biometric_files_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.biometric_files_id_seq OWNED BY public.biometric_files.id;


--
-- Name: business_hour_exceptions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.business_hour_exceptions (
    id integer NOT NULL,
    site_id integer,
    date date NOT NULL,
    open_at time without time zone,
    close_at time without time zone,
    closed boolean DEFAULT false,
    reason text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: business_hour_exceptions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.business_hour_exceptions_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: business_hour_exceptions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.business_hour_exceptions_id_seq OWNED BY public.business_hour_exceptions.id;


--
-- Name: business_hours; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.business_hours (
    id integer NOT NULL,
    site_id integer,
    weekday integer NOT NULL,
    open_at time without time zone,
    close_at time without time zone,
    closed boolean DEFAULT false,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT business_hours_weekday_check CHECK (((weekday >= 0) AND (weekday <= 6)))
);


--
-- Name: business_hours_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.business_hours_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: business_hours_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.business_hours_id_seq OWNED BY public.business_hours.id;


--
-- Name: data_retention_policies; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.data_retention_policies (
    id integer NOT NULL,
    entity_type character varying(50) NOT NULL,
    retention_period_months integer DEFAULT 60 NOT NULL,
    anonymization_period_months integer DEFAULT 72 NOT NULL,
    legal_basis text,
    purpose text NOT NULL,
    can_be_deleted boolean DEFAULT true,
    notes text,
    active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: data_retention_policies_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.data_retention_policies_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: data_retention_policies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.data_retention_policies_id_seq OWNED BY public.data_retention_policies.id;


--
-- Name: data_retention_tasks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.data_retention_tasks (
    id integer NOT NULL,
    entity_type character varying(50) NOT NULL,
    entity_id integer NOT NULL,
    task_type character varying(20) NOT NULL,
    scheduled_for timestamp without time zone NOT NULL,
    executed_at timestamp without time zone,
    status character varying(20) DEFAULT 'pending'::character varying,
    error_message text,
    created_by integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: data_retention_tasks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.data_retention_tasks_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: data_retention_tasks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.data_retention_tasks_id_seq OWNED BY public.data_retention_tasks.id;


--
-- Name: funcionarios; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.funcionarios (
    id integer NOT NULL,
    nome character varying(100) NOT NULL,
    cpf character varying(20) NOT NULL,
    cargo character varying(50),
    foto character varying(255),
    data_admissao date DEFAULT CURRENT_DATE,
    ativo boolean DEFAULT true,
    email character varying(100),
    telefone character varying(20),
    data_criacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    deleted_at timestamp without time zone,
    deletion_reason text,
    anonymized_at timestamp without time zone,
    biometric_id character varying(255)
);


--
-- Name: funcionarios_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.funcionarios_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: funcionarios_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.funcionarios_id_seq OWNED BY public.funcionarios.id;


--
-- Name: holidays; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.holidays (
    id integer NOT NULL,
    date date NOT NULL,
    name character varying(100) NOT NULL,
    scope character varying(10) DEFAULT 'global'::character varying,
    site_id integer,
    active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT holidays_check CHECK (((((scope)::text = 'global'::text) AND (site_id IS NULL)) OR (((scope)::text = 'site'::text) AND (site_id IS NOT NULL)))),
    CONSTRAINT holidays_scope_check CHECK (((scope)::text = ANY ((ARRAY['global'::character varying, 'site'::character varying])::text[])))
);


--
-- Name: holidays_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.holidays_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: holidays_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.holidays_id_seq OWNED BY public.holidays.id;


--
-- Name: lgpd_requests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.lgpd_requests (
    id integer NOT NULL,
    tipo character varying(50) NOT NULL,
    dados_titular character varying(255) NOT NULL,
    tabela character varying(100),
    campo character varying(100),
    valor_atual text,
    valor_novo text,
    tabelas_afetadas text,
    justificativa text NOT NULL,
    status character varying(20) DEFAULT 'pendente'::character varying,
    data_solicitacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    data_processamento timestamp without time zone,
    processado_por integer,
    observacoes text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: lgpd_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.lgpd_requests_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: lgpd_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.lgpd_requests_id_seq OWNED BY public.lgpd_requests.id;


--
-- Name: login_attempts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.login_attempts (
    id integer NOT NULL,
    ip_address inet NOT NULL,
    email character varying(255),
    attempts integer DEFAULT 1,
    last_attempt timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    locked_until timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: login_attempts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.login_attempts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: login_attempts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.login_attempts_id_seq OWNED BY public.login_attempts.id;


--
-- Name: organization_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.organization_settings (
    id integer NOT NULL,
    company_name character varying(120) NOT NULL,
    cnpj character varying(18),
    logo_url character varying(500),
    timezone character varying(50) DEFAULT 'America/Sao_Paulo'::character varying,
    locale character varying(10) DEFAULT 'pt-BR'::character varying,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT organization_settings_company_name_check CHECK ((char_length((company_name)::text) >= 2))
);


--
-- Name: organization_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.organization_settings_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: organization_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.organization_settings_id_seq OWNED BY public.organization_settings.id;


--
-- Name: password_resets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_resets (
    id integer NOT NULL,
    usuario_id integer,
    token character varying(255) NOT NULL,
    expires_at timestamp without time zone NOT NULL,
    created_at timestamp without time zone DEFAULT now(),
    used_at timestamp without time zone,
    ip_address character varying(45),
    user_agent text
);


--
-- Name: password_resets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.password_resets_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: password_resets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.password_resets_id_seq OWNED BY public.password_resets.id;


--
-- Name: permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permissions (
    id integer NOT NULL,
    key character varying(100) NOT NULL,
    description text,
    module character varying(50),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permissions_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: prestadores_servico; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.prestadores_servico (
    id integer NOT NULL,
    entrada timestamp without time zone,
    saida timestamp without time zone,
    nome character varying(255) NOT NULL,
    cpf character varying(14),
    observacao text,
    empresa character varying(255),
    setor character varying(255),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    placa_veiculo character varying(20),
    funcionario_responsavel character varying(255) DEFAULT ''::character varying,
    deleted_at timestamp without time zone,
    deletion_reason text,
    anonymized_at timestamp without time zone,
    CONSTRAINT chk_prestadores_horario_valido CHECK (((saida IS NULL) OR (saida >= entrada)))
);


--
-- Name: prestadores_servico_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.prestadores_servico_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: prestadores_servico_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.prestadores_servico_id_seq OWNED BY public.prestadores_servico.id;


--
-- Name: profissionais_renner; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.profissionais_renner (
    id integer NOT NULL,
    nome character varying(255) NOT NULL,
    data_entrada timestamp without time zone,
    saida timestamp without time zone,
    retorno timestamp without time zone,
    saida_final timestamp without time zone,
    setor character varying(255),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    cpf character varying(11) DEFAULT ''::character varying,
    placa_veiculo character varying(8) DEFAULT ''::character varying,
    empresa character varying(255) DEFAULT ''::character varying,
    fre character varying(50),
    data_admissao date
);


--
-- Name: profissionais_renner_backup; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.profissionais_renner_backup (
    id integer,
    nome character varying(255),
    data_entrada timestamp without time zone,
    saida timestamp without time zone,
    retorno timestamp without time zone,
    saida_final timestamp without time zone,
    setor character varying(255),
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    cpf character varying(11),
    placa_veiculo character varying(8),
    empresa character varying(255),
    fre character varying(50),
    data_admissao date
);


--
-- Name: profissionais_renner_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.profissionais_renner_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: profissionais_renner_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.profissionais_renner_id_seq OWNED BY public.profissionais_renner.id;


--
-- Name: registro_acesso; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.registro_acesso (
    id integer NOT NULL,
    tipo character varying(20) NOT NULL,
    nome character varying(100) NOT NULL,
    cpf character varying(11),
    empresa character varying(100),
    setor character varying(100),
    placa_veiculo character varying(10),
    funcionario_responsavel character varying(100),
    observacao text,
    entrada_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    saida_at timestamp without time zone,
    created_by integer,
    updated_by integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    profissional_renner_id integer,
    retorno timestamp without time zone,
    saida_final timestamp without time zone,
    CONSTRAINT check_saida_after_entrada CHECK (((saida_at IS NULL) OR (saida_at >= entrada_at))),
    CONSTRAINT chk_registro_horario_valido CHECK (((saida_at IS NULL) OR (saida_at >= entrada_at))),
    CONSTRAINT registro_acesso_tipo_check CHECK (((tipo)::text = ANY ((ARRAY['visitante'::character varying, 'prestador_servico'::character varying, 'profissional_renner'::character varying])::text[])))
);


--
-- Name: registro_acesso_backup; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.registro_acesso_backup (
    id integer,
    tipo character varying(20),
    nome character varying(100),
    cpf character varying(11),
    empresa character varying(100),
    setor character varying(100),
    placa_veiculo character varying(10),
    funcionario_responsavel character varying(100),
    observacao text,
    entrada_at timestamp without time zone,
    saida_at timestamp without time zone,
    created_by integer,
    updated_by integer,
    created_at timestamp without time zone,
    updated_at timestamp without time zone
);


--
-- Name: registro_acesso_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.registro_acesso_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: registro_acesso_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.registro_acesso_id_seq OWNED BY public.registro_acesso.id;


--
-- Name: reset_attempts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reset_attempts (
    id integer NOT NULL,
    ip_address character varying(45) NOT NULL,
    attempted_at timestamp without time zone DEFAULT now()
);


--
-- Name: reset_attempts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reset_attempts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reset_attempts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reset_attempts_id_seq OWNED BY public.reset_attempts.id;


--
-- Name: role_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_permissions (
    role_id integer NOT NULL,
    permission_id integer NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id integer NOT NULL,
    name character varying(50) NOT NULL,
    description text,
    system_role boolean DEFAULT false,
    active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.roles_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: sectors; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sectors (
    id integer NOT NULL,
    site_id integer,
    name character varying(100) NOT NULL,
    capacity integer DEFAULT 0,
    active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT sectors_capacity_check CHECK ((capacity >= 0))
);


--
-- Name: sectors_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sectors_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sectors_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sectors_id_seq OWNED BY public.sectors.id;


--
-- Name: sites; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sites (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    capacity integer DEFAULT 0,
    address text,
    timezone character varying(50) DEFAULT 'America/Sao_Paulo'::character varying,
    active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT sites_capacity_check CHECK ((capacity >= 0))
);


--
-- Name: sites_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sites_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sites_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sites_id_seq OWNED BY public.sites.id;


--
-- Name: usuarios; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usuarios (
    id integer NOT NULL,
    nome character varying(100) NOT NULL,
    email character varying(100) NOT NULL,
    senha_hash character varying(255) NOT NULL,
    perfil character varying(20) DEFAULT 'porteiro'::character varying,
    ativo boolean DEFAULT true,
    data_criacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    ultimo_login timestamp without time zone,
    role_id integer,
    anonymized_at timestamp without time zone,
    CONSTRAINT usuarios_perfil_check CHECK (((perfil)::text = ANY ((ARRAY['administrador'::character varying, 'porteiro'::character varying, 'seguranca'::character varying, 'recepcao'::character varying])::text[])))
);


--
-- Name: usuarios_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.usuarios_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usuarios_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.usuarios_id_seq OWNED BY public.usuarios.id;


--
-- Name: visitantes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.visitantes (
    id integer NOT NULL,
    nome character varying(100) NOT NULL,
    cpf character varying(20),
    empresa character varying(100),
    pessoa_visitada character varying(100),
    foto character varying(255),
    email character varying(100),
    telefone character varying(20),
    qr_code character varying(255),
    status character varying(20) DEFAULT 'ativo'::character varying,
    data_cadastro timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    data_vencimento timestamp without time zone,
    placa_veiculo character varying(20),
    CONSTRAINT visitantes_status_check CHECK (((status)::text = ANY ((ARRAY['ativo'::character varying, 'saiu'::character varying, 'vencido'::character varying])::text[])))
);


--
-- Name: visitantes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.visitantes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: visitantes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.visitantes_id_seq OWNED BY public.visitantes.id;


--
-- Name: visitantes_novo; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.visitantes_novo (
    id integer NOT NULL,
    hora_entrada timestamp without time zone,
    hora_saida timestamp without time zone,
    nome character varying(255) NOT NULL,
    cpf character varying(14),
    empresa character varying(255),
    funcionario_responsavel character varying(255),
    setor character varying(255),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    placa_veiculo character varying(20),
    deleted_at timestamp without time zone,
    deletion_reason text,
    anonymized_at timestamp without time zone,
    CONSTRAINT chk_visitantes_horario_valido CHECK (((hora_saida IS NULL) OR (hora_saida >= hora_entrada)))
);


--
-- Name: visitantes_novo_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.visitantes_novo_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: visitantes_novo_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.visitantes_novo_id_seq OWNED BY public.visitantes_novo.id;


--
-- Name: acessos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.acessos ALTER COLUMN id SET DEFAULT nextval('public.acessos_id_seq'::regclass);


--
-- Name: audit_log id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_log ALTER COLUMN id SET DEFAULT nextval('public.audit_log_id_seq'::regclass);


--
-- Name: auth_policies id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auth_policies ALTER COLUMN id SET DEFAULT nextval('public.auth_policies_id_seq'::regclass);


--
-- Name: biometric_files id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.biometric_files ALTER COLUMN id SET DEFAULT nextval('public.biometric_files_id_seq'::regclass);


--
-- Name: business_hour_exceptions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.business_hour_exceptions ALTER COLUMN id SET DEFAULT nextval('public.business_hour_exceptions_id_seq'::regclass);


--
-- Name: business_hours id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.business_hours ALTER COLUMN id SET DEFAULT nextval('public.business_hours_id_seq'::regclass);


--
-- Name: data_retention_policies id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.data_retention_policies ALTER COLUMN id SET DEFAULT nextval('public.data_retention_policies_id_seq'::regclass);


--
-- Name: data_retention_tasks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.data_retention_tasks ALTER COLUMN id SET DEFAULT nextval('public.data_retention_tasks_id_seq'::regclass);


--
-- Name: funcionarios id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.funcionarios ALTER COLUMN id SET DEFAULT nextval('public.funcionarios_id_seq'::regclass);


--
-- Name: holidays id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.holidays ALTER COLUMN id SET DEFAULT nextval('public.holidays_id_seq'::regclass);


--
-- Name: lgpd_requests id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lgpd_requests ALTER COLUMN id SET DEFAULT nextval('public.lgpd_requests_id_seq'::regclass);


--
-- Name: login_attempts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.login_attempts ALTER COLUMN id SET DEFAULT nextval('public.login_attempts_id_seq'::regclass);


--
-- Name: organization_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organization_settings ALTER COLUMN id SET DEFAULT nextval('public.organization_settings_id_seq'::regclass);


--
-- Name: password_resets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_resets ALTER COLUMN id SET DEFAULT nextval('public.password_resets_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: prestadores_servico id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.prestadores_servico ALTER COLUMN id SET DEFAULT nextval('public.prestadores_servico_id_seq'::regclass);


--
-- Name: profissionais_renner id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.profissionais_renner ALTER COLUMN id SET DEFAULT nextval('public.profissionais_renner_id_seq'::regclass);


--
-- Name: registro_acesso id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registro_acesso ALTER COLUMN id SET DEFAULT nextval('public.registro_acesso_id_seq'::regclass);


--
-- Name: reset_attempts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reset_attempts ALTER COLUMN id SET DEFAULT nextval('public.reset_attempts_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: sectors id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sectors ALTER COLUMN id SET DEFAULT nextval('public.sectors_id_seq'::regclass);


--
-- Name: sites id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sites ALTER COLUMN id SET DEFAULT nextval('public.sites_id_seq'::regclass);


--
-- Name: usuarios id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios ALTER COLUMN id SET DEFAULT nextval('public.usuarios_id_seq'::regclass);


--
-- Name: visitantes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.visitantes ALTER COLUMN id SET DEFAULT nextval('public.visitantes_id_seq'::regclass);


--
-- Name: visitantes_novo id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.visitantes_novo ALTER COLUMN id SET DEFAULT nextval('public.visitantes_novo_id_seq'::regclass);


--
-- Name: acessos acessos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.acessos
    ADD CONSTRAINT acessos_pkey PRIMARY KEY (id);


--
-- Name: audit_log audit_log_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_log
    ADD CONSTRAINT audit_log_pkey PRIMARY KEY (id);


--
-- Name: auth_policies auth_policies_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auth_policies
    ADD CONSTRAINT auth_policies_pkey PRIMARY KEY (id);


--
-- Name: biometric_files biometric_files_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.biometric_files
    ADD CONSTRAINT biometric_files_pkey PRIMARY KEY (id);


--
-- Name: biometric_files biometric_files_secure_filename_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.biometric_files
    ADD CONSTRAINT biometric_files_secure_filename_key UNIQUE (secure_filename);


--
-- Name: business_hour_exceptions business_hour_exceptions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.business_hour_exceptions
    ADD CONSTRAINT business_hour_exceptions_pkey PRIMARY KEY (id);


--
-- Name: business_hour_exceptions business_hour_exceptions_site_id_date_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.business_hour_exceptions
    ADD CONSTRAINT business_hour_exceptions_site_id_date_key UNIQUE (site_id, date);


--
-- Name: business_hours business_hours_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.business_hours
    ADD CONSTRAINT business_hours_pkey PRIMARY KEY (id);


--
-- Name: business_hours business_hours_site_id_weekday_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.business_hours
    ADD CONSTRAINT business_hours_site_id_weekday_key UNIQUE (site_id, weekday);


--
-- Name: data_retention_policies data_retention_policies_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.data_retention_policies
    ADD CONSTRAINT data_retention_policies_pkey PRIMARY KEY (id);


--
-- Name: data_retention_tasks data_retention_tasks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.data_retention_tasks
    ADD CONSTRAINT data_retention_tasks_pkey PRIMARY KEY (id);


--
-- Name: funcionarios funcionarios_cpf_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.funcionarios
    ADD CONSTRAINT funcionarios_cpf_key UNIQUE (cpf);


--
-- Name: funcionarios funcionarios_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.funcionarios
    ADD CONSTRAINT funcionarios_pkey PRIMARY KEY (id);


--
-- Name: holidays holidays_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.holidays
    ADD CONSTRAINT holidays_pkey PRIMARY KEY (id);


--
-- Name: lgpd_requests lgpd_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lgpd_requests
    ADD CONSTRAINT lgpd_requests_pkey PRIMARY KEY (id);


--
-- Name: login_attempts login_attempts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.login_attempts
    ADD CONSTRAINT login_attempts_pkey PRIMARY KEY (id);


--
-- Name: organization_settings organization_settings_cnpj_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organization_settings
    ADD CONSTRAINT organization_settings_cnpj_key UNIQUE (cnpj);


--
-- Name: organization_settings organization_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organization_settings
    ADD CONSTRAINT organization_settings_pkey PRIMARY KEY (id);


--
-- Name: password_resets password_resets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_resets
    ADD CONSTRAINT password_resets_pkey PRIMARY KEY (id);


--
-- Name: password_resets password_resets_token_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_resets
    ADD CONSTRAINT password_resets_token_key UNIQUE (token);


--
-- Name: permissions permissions_key_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_key_key UNIQUE (key);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: prestadores_servico prestadores_servico_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.prestadores_servico
    ADD CONSTRAINT prestadores_servico_pkey PRIMARY KEY (id);


--
-- Name: profissionais_renner profissionais_renner_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.profissionais_renner
    ADD CONSTRAINT profissionais_renner_pkey PRIMARY KEY (id);


--
-- Name: registro_acesso registro_acesso_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registro_acesso
    ADD CONSTRAINT registro_acesso_pkey PRIMARY KEY (id);


--
-- Name: reset_attempts reset_attempts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reset_attempts
    ADD CONSTRAINT reset_attempts_pkey PRIMARY KEY (id);


--
-- Name: role_permissions role_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_pkey PRIMARY KEY (role_id, permission_id);


--
-- Name: roles roles_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_key UNIQUE (name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: sectors sectors_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sectors
    ADD CONSTRAINT sectors_pkey PRIMARY KEY (id);


--
-- Name: sectors sectors_site_id_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sectors
    ADD CONSTRAINT sectors_site_id_name_key UNIQUE (site_id, name);


--
-- Name: sites sites_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sites
    ADD CONSTRAINT sites_pkey PRIMARY KEY (id);


--
-- Name: usuarios usuarios_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_email_key UNIQUE (email);


--
-- Name: usuarios usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_pkey PRIMARY KEY (id);


--
-- Name: visitantes_novo visitantes_novo_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.visitantes_novo
    ADD CONSTRAINT visitantes_novo_pkey PRIMARY KEY (id);


--
-- Name: visitantes visitantes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.visitantes
    ADD CONSTRAINT visitantes_pkey PRIMARY KEY (id);


--
-- Name: idx_acessos_data_hora; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_acessos_data_hora ON public.acessos USING btree (data_hora);


--
-- Name: idx_acessos_funcionario; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_acessos_funcionario ON public.acessos USING btree (funcionario_id);


--
-- Name: idx_acessos_visitante; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_acessos_visitante ON public.acessos USING btree (visitante_id);


--
-- Name: idx_audit_log_acao; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_audit_log_acao ON public.audit_log USING btree (acao);


--
-- Name: idx_audit_log_entidade; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_audit_log_entidade ON public.audit_log USING btree (entidade, entidade_id);


--
-- Name: idx_audit_log_modulo; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_audit_log_modulo ON public.audit_log USING btree (modulo);


--
-- Name: idx_audit_log_severidade; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_audit_log_severidade ON public.audit_log USING btree (severidade);


--
-- Name: idx_audit_log_timestamp_severidade; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_audit_log_timestamp_severidade ON public.audit_log USING btree ("timestamp" DESC, severidade);


--
-- Name: idx_audit_log_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_audit_log_user_id ON public.audit_log USING btree (user_id);


--
-- Name: idx_biometric_created; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_biometric_created ON public.biometric_files USING btree (created_at);


--
-- Name: idx_biometric_deleted; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_biometric_deleted ON public.biometric_files USING btree (deleted_at);


--
-- Name: idx_biometric_entity; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_biometric_entity ON public.biometric_files USING btree (entity_type, entity_id);


--
-- Name: idx_business_hour_exceptions_site_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_business_hour_exceptions_site_date ON public.business_hour_exceptions USING btree (site_id, date);


--
-- Name: idx_business_hours_site_weekday; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_business_hours_site_weekday ON public.business_hours USING btree (site_id, weekday);


--
-- Name: idx_data_retention_active; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_data_retention_active ON public.data_retention_policies USING btree (active);


--
-- Name: idx_data_retention_entity; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_data_retention_entity ON public.data_retention_policies USING btree (entity_type);


--
-- Name: idx_funcionarios_ativo; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_funcionarios_ativo ON public.funcionarios USING btree (ativo);


--
-- Name: idx_funcionarios_created_deleted; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_funcionarios_created_deleted ON public.funcionarios USING btree (data_criacao, deleted_at);


--
-- Name: idx_holidays_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_holidays_date ON public.holidays USING btree (date);


--
-- Name: idx_holidays_site_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_holidays_site_date ON public.holidays USING btree (site_id, date);


--
-- Name: idx_prestadores_created_deleted; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_prestadores_created_deleted ON public.prestadores_servico USING btree (created_at, deleted_at);


--
-- Name: idx_prestadores_data_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_prestadores_data_status ON public.prestadores_servico USING btree (date(entrada), saida);


--
-- Name: idx_prestadores_empresa; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_prestadores_empresa ON public.prestadores_servico USING btree (empresa);


--
-- Name: idx_prestadores_entrada_desc; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_prestadores_entrada_desc ON public.prestadores_servico USING btree (entrada DESC NULLS LAST);


--
-- Name: idx_prestadores_entrada_not_null; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_prestadores_entrada_not_null ON public.prestadores_servico USING btree (entrada) WHERE (entrada IS NOT NULL);


--
-- Name: idx_prestadores_responsavel; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_prestadores_responsavel ON public.prestadores_servico USING btree (funcionario_responsavel);


--
-- Name: idx_prestadores_setor; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_prestadores_setor ON public.prestadores_servico USING btree (setor);


--
-- Name: idx_profissionais_data_entrada; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_profissionais_data_entrada ON public.profissionais_renner USING btree (data_entrada DESC);


--
-- Name: idx_profissionais_relatorio; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_profissionais_relatorio ON public.profissionais_renner USING btree (data_entrada DESC, saida_final);


--
-- Name: idx_profissionais_setor; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_profissionais_setor ON public.profissionais_renner USING btree (setor);


--
-- Name: idx_profissionais_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_profissionais_status ON public.profissionais_renner USING btree (saida_final);


--
-- Name: idx_registro_acesso_alertas; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_registro_acesso_alertas ON public.registro_acesso USING btree (entrada_at, saida_at) WHERE (saida_at IS NULL);


--
-- Name: idx_registro_acesso_cpf; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_registro_acesso_cpf ON public.registro_acesso USING btree (cpf);


--
-- Name: idx_registro_acesso_entrada_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_registro_acesso_entrada_at ON public.registro_acesso USING btree (entrada_at);


--
-- Name: idx_registro_acesso_placa; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_registro_acesso_placa ON public.registro_acesso USING btree (placa_veiculo);


--
-- Name: idx_registro_acesso_placa_open; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX idx_registro_acesso_placa_open ON public.registro_acesso USING btree (placa_veiculo) WHERE ((saida_final IS NULL) AND (placa_veiculo IS NOT NULL) AND ((placa_veiculo)::text <> 'APE'::text) AND ((placa_veiculo)::text <> ''::text));


--
-- Name: idx_registro_acesso_profissional; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_registro_acesso_profissional ON public.registro_acesso USING btree (profissional_renner_id, entrada_at DESC);


--
-- Name: idx_registro_acesso_saida_null; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_registro_acesso_saida_null ON public.registro_acesso USING btree (saida_at) WHERE (saida_at IS NULL);


--
-- Name: idx_retention_tasks_entity; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_retention_tasks_entity ON public.data_retention_tasks USING btree (entity_type, entity_id);


--
-- Name: idx_retention_tasks_scheduled; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_retention_tasks_scheduled ON public.data_retention_tasks USING btree (scheduled_for, status);


--
-- Name: idx_retention_tasks_status_scheduled; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_retention_tasks_status_scheduled ON public.data_retention_tasks USING btree (status, scheduled_for);


--
-- Name: idx_role_permissions_permission; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_role_permissions_permission ON public.role_permissions USING btree (permission_id);


--
-- Name: idx_role_permissions_role; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_role_permissions_role ON public.role_permissions USING btree (role_id);


--
-- Name: idx_sectors_active; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sectors_active ON public.sectors USING btree (active);


--
-- Name: idx_sectors_site_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sectors_site_id ON public.sectors USING btree (site_id);


--
-- Name: idx_sites_active; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_sites_active ON public.sites USING btree (active);


--
-- Name: idx_usuarios_created_anonymized; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_usuarios_created_anonymized ON public.usuarios USING btree (data_criacao, anonymized_at);


--
-- Name: idx_usuarios_role; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_usuarios_role ON public.usuarios USING btree (role_id);


--
-- Name: idx_visitantes_created_deleted; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_visitantes_created_deleted ON public.visitantes_novo USING btree (created_at, deleted_at);


--
-- Name: idx_visitantes_novo_empresa; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_visitantes_novo_empresa ON public.visitantes_novo USING btree (empresa);


--
-- Name: idx_visitantes_novo_entrada_data; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_visitantes_novo_entrada_data ON public.visitantes_novo USING btree (date(hora_entrada));


--
-- Name: idx_visitantes_novo_entrada_ordenacao; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_visitantes_novo_entrada_ordenacao ON public.visitantes_novo USING btree (hora_entrada DESC);


--
-- Name: idx_visitantes_novo_responsavel; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_visitantes_novo_responsavel ON public.visitantes_novo USING btree (funcionario_responsavel);


--
-- Name: idx_visitantes_novo_setor; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_visitantes_novo_setor ON public.visitantes_novo USING btree (setor);


--
-- Name: idx_visitantes_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_visitantes_status ON public.visitantes USING btree (status);


--
-- Name: uq_login_attempts_ip_email; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uq_login_attempts_ip_email ON public.login_attempts USING btree (ip_address, email) WHERE (email IS NOT NULL);


--
-- Name: uq_login_attempts_ip_null; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uq_login_attempts_ip_null ON public.login_attempts USING btree (ip_address) WHERE (email IS NULL);


--
-- Name: ux_prestadores_cpf_ativo; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX ux_prestadores_cpf_ativo ON public.prestadores_servico USING btree (cpf) WHERE ((cpf IS NOT NULL) AND ((cpf)::text <> ''::text) AND (saida IS NULL));


--
-- Name: ux_prestadores_placa_ativa; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX ux_prestadores_placa_ativa ON public.prestadores_servico USING btree (placa_veiculo) WHERE ((placa_veiculo IS NOT NULL) AND ((placa_veiculo)::text <> ''::text) AND ((placa_veiculo)::text <> 'APE'::text) AND (saida IS NULL));


--
-- Name: ux_profissionais_placa_ativa; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX ux_profissionais_placa_ativa ON public.profissionais_renner USING btree (placa_veiculo) WHERE ((placa_veiculo IS NOT NULL) AND ((placa_veiculo)::text <> ''::text) AND ((placa_veiculo)::text <> 'APE'::text) AND (saida_final IS NULL));


--
-- Name: ux_registro_cpf_ativo; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX ux_registro_cpf_ativo ON public.registro_acesso USING btree (cpf) WHERE ((cpf IS NOT NULL) AND ((cpf)::text <> ''::text) AND (saida_at IS NULL));


--
-- Name: ux_visitantes_cpf_ativo; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX ux_visitantes_cpf_ativo ON public.visitantes_novo USING btree (cpf) WHERE ((cpf IS NOT NULL) AND ((cpf)::text <> ''::text) AND (hora_saida IS NULL));


--
-- Name: ux_visitantes_placa_ativa; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX ux_visitantes_placa_ativa ON public.visitantes_novo USING btree (placa_veiculo) WHERE ((placa_veiculo IS NOT NULL) AND ((placa_veiculo)::text <> ''::text) AND ((placa_veiculo)::text <> 'APE'::text) AND (hora_saida IS NULL));


--
-- Name: auth_policies update_auth_policies_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_auth_policies_updated_at BEFORE UPDATE ON public.auth_policies FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: organization_settings update_organization_settings_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_organization_settings_updated_at BEFORE UPDATE ON public.organization_settings FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: roles update_roles_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_roles_updated_at BEFORE UPDATE ON public.roles FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: sectors update_sectors_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_sectors_updated_at BEFORE UPDATE ON public.sectors FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: sites update_sites_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_sites_updated_at BEFORE UPDATE ON public.sites FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: acessos acessos_funcionario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.acessos
    ADD CONSTRAINT acessos_funcionario_id_fkey FOREIGN KEY (funcionario_id) REFERENCES public.funcionarios(id) ON DELETE SET NULL;


--
-- Name: acessos acessos_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.acessos
    ADD CONSTRAINT acessos_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id) ON DELETE SET NULL;


--
-- Name: acessos acessos_visitante_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.acessos
    ADD CONSTRAINT acessos_visitante_id_fkey FOREIGN KEY (visitante_id) REFERENCES public.visitantes(id) ON DELETE SET NULL;


--
-- Name: audit_log audit_log_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_log
    ADD CONSTRAINT audit_log_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.usuarios(id) ON DELETE SET NULL;


--
-- Name: biometric_files biometric_files_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.biometric_files
    ADD CONSTRAINT biometric_files_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.usuarios(id);


--
-- Name: business_hour_exceptions business_hour_exceptions_site_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.business_hour_exceptions
    ADD CONSTRAINT business_hour_exceptions_site_id_fkey FOREIGN KEY (site_id) REFERENCES public.sites(id) ON DELETE CASCADE;


--
-- Name: business_hours business_hours_site_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.business_hours
    ADD CONSTRAINT business_hours_site_id_fkey FOREIGN KEY (site_id) REFERENCES public.sites(id) ON DELETE CASCADE;


--
-- Name: data_retention_tasks data_retention_tasks_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.data_retention_tasks
    ADD CONSTRAINT data_retention_tasks_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.usuarios(id);


--
-- Name: registro_acesso fk_registro_acesso_profissional; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registro_acesso
    ADD CONSTRAINT fk_registro_acesso_profissional FOREIGN KEY (profissional_renner_id) REFERENCES public.profissionais_renner(id) ON DELETE RESTRICT;


--
-- Name: holidays holidays_site_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.holidays
    ADD CONSTRAINT holidays_site_id_fkey FOREIGN KEY (site_id) REFERENCES public.sites(id) ON DELETE CASCADE;


--
-- Name: lgpd_requests lgpd_requests_processado_por_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lgpd_requests
    ADD CONSTRAINT lgpd_requests_processado_por_fkey FOREIGN KEY (processado_por) REFERENCES public.usuarios(id);


--
-- Name: password_resets password_resets_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_resets
    ADD CONSTRAINT password_resets_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- Name: registro_acesso registro_acesso_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registro_acesso
    ADD CONSTRAINT registro_acesso_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.usuarios(id) ON DELETE SET NULL;


--
-- Name: registro_acesso registro_acesso_updated_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registro_acesso
    ADD CONSTRAINT registro_acesso_updated_by_fkey FOREIGN KEY (updated_by) REFERENCES public.usuarios(id) ON DELETE SET NULL;


--
-- Name: role_permissions role_permissions_permission_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_permissions role_permissions_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: sectors sectors_site_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sectors
    ADD CONSTRAINT sectors_site_id_fkey FOREIGN KEY (site_id) REFERENCES public.sites(id) ON DELETE CASCADE;


--
-- Name: usuarios usuarios_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.roles(id);


--
-- PostgreSQL database dump complete
--

