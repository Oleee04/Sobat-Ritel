const TradingView = require('@mathieuc/tradingview');
const mysql = require('mysql2');

// Konfigurasi Database
const connection = mysql.createConnection({
  host: 'localhost',
  user: 'root',
  password: '',
  database: 'screening_saham'
});

connection.connect((err) => {
  if (err) throw err;
  console.log('Terhubung ke database MySQL.');
});

const client = new TradingView.Client(); // WebSocket client

// Daftar saham yang ingin kita tarik harganya secara real-time (contoh Blue Chips)
const topStocks = ['BBCA', 'BBRI', 'BMRI', 'BBNI', 'TLKM', 'ASII', 'GOTO', 'ADRO', 'UNVR', 'ICBP'];

topStocks.forEach(ticker => {
  const chart = new client.Session.Chart();
  
  chart.setMarket(`IDX:${ticker}`, {
    timeframe: 'D',
  });

  chart.onError((...err) => {
    console.error(`Error pada chart ${ticker}:`, ...err);
  });

  chart.onSymbolLoaded(() => {
    console.log(`Memantau saham: ${ticker}`);
  });

  chart.onUpdate(() => {
    if (!chart.periods[0]) return;
    const currentPrice = chart.periods[0].close;
    
    // Log di console
    console.log(`[UPDATE] ${ticker}: Rp${currentPrice}`);

    // Update database
    const query = `UPDATE stocks SET price = ? WHERE ticker = ?`;
    connection.execute(query, [currentPrice, ticker], (err, results) => {
      if (err) {
        console.error(`Gagal update database untuk ${ticker}:`, err.message);
      }
    });
  });
});
