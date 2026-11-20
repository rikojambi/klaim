<?php
/**
 * header.php
 * Komponen header & navigasi sidebar yang dipakai di semua halaman.
 *
 * Variabel yang HARUS tersedia sebelum include ini:
 *   $pageTitle  (string) — judul tab browser
 *   $activePage (string) — nama menu aktif untuk styling
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// Tentukan base path untuk asset
$base = getBasePath();  // fungsi dari auth_check.php

$role      = $_SESSION['role']        ?? '';
$namaUser  = $_SESSION['nama_lengkap'] ?? 'Pengguna';
$username  = $_SESSION['username']    ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'BPJS Ketenagakerjaan') ?> — Sistem Klaim BPJS</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body>

<!-- ===================== SIDEBAR ===================== -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">
            <span class="logo-icon">&#9632;</span>
            <div class="brand-text">
                <span class="brand-title">BPJS</span>
                <span class="brand-subtitle">Ketenagakerjaan</span>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Menu Utama</div>

        <?php if ($role === 'Admin'): ?>
        <!-- MENU ADMIN -->
        <a href="<?= $base ?>/admin/dashboard.php"
           class="nav-item <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
            <span class="nav-icon">&#9733;</span>
            <span class="nav-text">Dashboard</span>
        </a>
        <a href="<?= $base ?>/admin/users.php"
           class="nav-item <?= ($activePage ?? '') === 'users' ? 'active' : '' ?>">
            <span class="nav-icon">&#9786;</span>
            <span class="nav-text">Manajemen User</span>
        </a>
        <a href="<?= $base ?>/admin/klaim.php"
           class="nav-item <?= ($activePage ?? '') === 'klaim' ? 'active' : '' ?>">
            <span class="nav-icon">&#9654;</span>
            <span class="nav-text">Data Klaim</span>
        </a>
        <a href="<?= $base ?>/admin/laporan.php"
           class="nav-item <?= ($activePage ?? '') === 'laporan' ? 'active' : '' ?>">
            <span class="nav-icon">&#9998;</span>
            <span class="nav-text">Cetak Laporan</span>
        </a>

        <?php elseif ($role === 'Petugas'): ?>
        <!-- MENU PETUGAS -->
        <a href="<?= $base ?>/petugas/dashboard.php"
           class="nav-item <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
            <span class="nav-icon">&#9733;</span>
            <span class="nav-text">Dashboard</span>
        </a>
        <a href="<?= $base ?>/petugas/peserta.php"
           class="nav-item <?= ($activePage ?? '') === 'peserta' ? 'active' : '' ?>">
            <span class="nav-icon">&#9786;</span>
            <span class="nav-text">Data Peserta</span>
        </a>
        <a href="<?= $base ?>/petugas/klaim_tambah.php"
           class="nav-item <?= ($activePage ?? '') === 'klaim_tambah' ? 'active' : '' ?>">
            <span class="nav-icon">&#43;</span>
            <span class="nav-text">Rekam Klaim</span>
        </a>
        <a href="<?= $base ?>/petugas/klaim_status.php"
           class="nav-item <?= ($activePage ?? '') === 'klaim_status' ? 'active' : '' ?>">
            <span class="nav-icon">&#9654;</span>
            <span class="nav-text">Status Klaim</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($namaUser, 0, 1)) ?></div>
            <div class="user-detail">
                <span class="user-name"><?= htmlspecialchars($namaUser) ?></span>
                <span class="user-role badge-<?= strtolower($role) ?>"><?= htmlspecialchars($role) ?></span>
            </div>
        </div>
        <a href="<?= $base ?>/logout.php" class="btn-logout" title="Keluar">&#9664; Keluar</a>
    </div>
</aside>

<!-- ===================== MAIN WRAPPER ===================== -->
<div class="main-wrapper">

    <!-- TOPBAR -->
    <header class="topbar">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">&#9776;</button>
        <div class="topbar-title">
            <h1><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
        </div>
        <div class="topbar-right">
            <span class="topbar-date"><?= date('l, d F Y') ?></span>
        </div>
    </header>

    <!-- KONTEN UTAMA dimulai setelah ini -->
    <main class="content-area">
