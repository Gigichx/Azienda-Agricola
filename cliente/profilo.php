<?php
/**
 * PROFILO.PHP - Cliente
 * Azienda Agricola
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCliente();

$pageTitle = 'Il Mio Profilo';

// Ottieni dati cliente
$sqlCliente = "SELECT c.*, u.email, u.dataRegistrazione
               FROM CLIENTE c
               INNER JOIN UTENTE u ON c.idUtente = u.idUtente
               WHERE u.idUtente = ?";
$cliente = fetchOne($pdo, $sqlCliente, [getUserId()]);

// Ottieni storico ordini
$sqlOrdini = "SELECT v.*, l.nome as nomeLuogo,
              (SELECT COUNT(*) FROM DETTAGLIO_VENDITA WHERE idVendita = v.idVendita) as numeroArticoli
              FROM VENDITA v
              INNER JOIN LUOGO l ON v.idLuogo = l.idLuogo
              WHERE v.idCliente = ?
              ORDER BY v.dataVendita DESC
              LIMIT 20";

$ordini = fetchAll($pdo, $sqlOrdini, [$cliente['idCliente']]);

// Calcola statistiche
$sqlStats = "SELECT 
             COUNT(*) as totaleOrdini,
             SUM(totalePagato) as totaleSpeso
             FROM VENDITA
             WHERE idCliente = ?";
$stats = fetchOne($pdo, $sqlStats, [$cliente['idCliente']]);

include '../includes/header_cliente.php';
?>

<div class="profilo-container">
    <!-- Header Profilo -->
    <div class="profilo-header">
        <div class="profilo-info">
            <div class="profilo-avatar">
                <?php echo strtoupper(substr($cliente['nome'], 0, 1)); ?>
            </div>
            <div class="profilo-details">
                <h2><?php echo htmlspecialchars($cliente['nome']); ?></h2>
                <p class="profilo-email"><?php echo htmlspecialchars($cliente['email']); ?></p>
                <?php if ($cliente['telefono']): ?>
                    <p class="profilo-email">📞 <?php echo htmlspecialchars($cliente['telefono']); ?></p>
                <?php endif; ?>
                <p class="profilo-since">Cliente dal <?php echo formatDate($cliente['dataRegistrazione']); ?></p>
            </div>
        </div>
        
        <!-- Statistiche Quick -->
        <div class="row" style="margin-top: 2rem;">
            <div class="col">
                <div class="stat-quick">
                    <div class="stat-icon" style="background-color: var(--color-primary);">📦</div>
                    <div class="stat-content">
                        <div class="stat-label">Ordini Totali</div>
                        <div class="stat-value"><?php echo $stats['totaleOrdini']; ?></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="stat-quick">
                    <div class="stat-icon" style="background-color: var(--color-accent);">💰</div>
                    <div class="stat-content">
                        <div class="stat-label">Totale Speso</div>
                        <div class="stat-value"><?php echo formatPrice($stats['totaleSpeso'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Storico Ordini -->
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">I Miei Ordini</h2>
        </div>
        
        <?php if (empty($ordini)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <h3 class="empty-state-title">Nessun ordine ancora</h3>
                <p class="empty-state-message">Inizia a fare acquisti dal nostro catalogo!</p>
                <a href="/cliente/catalogo.php" class="btn btn-primary">Vai al Catalogo</a>
            </div>
        <?php else: ?>
            <div class="ordini-lista">
                <?php foreach ($ordini as $ordine): ?>
                    <div class="ordine-card">
                        <div class="ordine-header">
                            <div>
                                <div class="ordine-numero">Ordine #<?php echo $ordine['idVendita']; ?></div>
                                <div class="ordine-data"><?php echo formatDate($ordine['dataVendita'], true); ?></div>
                            </div>
                            <div>
                                <span class="badge badge-success">Completato</span>
                            </div>
                        </div>
                        
                        <?php
                        // Ottieni dettagli ordine
                        $sqlDettagli = "SELECT dv.*, p.nome as nomeProdotto
                                       FROM DETTAGLIO_VENDITA dv
                                       INNER JOIN PRODOTTO p ON dv.idProdotto = p.idProdotto
                                       WHERE dv.idVendita = ?";
                        $dettagli = fetchAll($pdo, $sqlDettagli, [$ordine['idVendita']]);
                        ?>
                        
                        <ul class="ordine-items">
                            <?php foreach ($dettagli as $det): ?>
                                <li class="ordine-item">
                                    <strong><?php echo htmlspecialchars($det['nomeProdotto']); ?></strong>
                                    <?php if ($det['quantita']): ?>
                                        - Quantità: <?php echo $det['quantita']; ?>
                                    <?php endif; ?>
                                    <?php if ($det['pesoVenduto']): ?>
                                        - Peso: <?php echo formatWeight($det['pesoVenduto'], 'kg'); ?>
                                    <?php endif; ?>
                                    - <?php echo formatPrice($det['prezzoUnitario']); ?>
                                    <?php if ($det['omaggio']): ?>
                                        <span class="badge badge-warning">Omaggio</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <?php if ($ordine['note']): ?>
                            <div class="info-card" style="margin-bottom: 1rem;">
                                <div class="info-card-content">
                                    <p><strong>Note:</strong> <?php echo htmlspecialchars($ordine['note']); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="ordine-footer">
                            <div>
                                <small style="color: var(--color-text-muted);">
                                    Luogo: <?php echo htmlspecialchars($ordine['nomeLuogo']); ?>
                                </small>
                            </div>
                            <div class="ordine-totale">
                                <?php echo formatPrice($ordine['totalePagato']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
