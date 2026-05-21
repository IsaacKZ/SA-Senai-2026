"""
SISTEMA DE GESTÃO FARMACÊUTICA
Arquivo de Configuração Central
"""

import os
from datetime import timedelta

class Config:
    """
    Classe de configuração para o sistema Flask.
    Centraliza todos os parâmetros sensíveis e configurações do projeto.
    """
    
    # ========================================
    # CONFIGURAÇÕES DO FLASK
    # ========================================
    SECRET_KEY = os.environ.get('SECRET_KEY') or 'chave-secreta-farmacia-senai-2025'
    
    # Configuração de sessão
    PERMANENT_SESSION_LIFETIME = timedelta(hours=2)
    SESSION_COOKIE_HTTPONLY = True
    SESSION_COOKIE_SECURE = False
    SESSION_PERMANENT = False
    
    # ========================================
    # CONFIGURAÇÕES DO BANCO DE DADOS (SQLite)
    # ========================================
    # Caminho do arquivo do banco SQLite
    BASE_DIR = os.path.dirname(os.path.abspath(__file__))
    DATABASE_PATH = os.path.join(BASE_DIR, 'farmacia.db')
    
    # ========================================
    # VALIDAÇÃO DE ARQUIVOS (Receitas - só valida, não salva)
    # ========================================
    ALLOWED_EXTENSIONS = {'pdf', 'jpg', 'jpeg', 'png'}
    
    # ========================================
    # CONFIGURAÇÕES DE APLICAÇÃO
    # ========================================
    DEBUG = os.environ.get('FLASK_DEBUG') or True
    PRODUTOS_POR_PAGINA = 20
    DIAS_ALERTA_VALIDADE = 30
    
    # Senha mestra do supervisor (RN1 - Medicamentos Controlados)
    SENHA_SUPERVISOR_MESTRA = 'farmacia_VS'
    
    # ========================================
    # FUNÇÕES AUXILIARES
    # ========================================
    @staticmethod
    def allowed_file(filename):
        """Verifica se a extensão do arquivo é permitida."""
        return '.' in filename and \
               filename.rsplit('.', 1)[1].lower() in Config.ALLOWED_EXTENSIONS
    
    @staticmethod
    def init_app(app):
        """Inicializa configurações no app Flask."""
        print(f"[CONFIG] Banco de dados: {Config.DATABASE_PATH}")
