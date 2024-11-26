<script>
    import { onMount } from 'svelte';
  
    let balance = 0;
    let amount = '';
    let pin = '';
    let isAuthenticated = false;
    let message = '';
    let userId = '1'; // This would normally come from authentication
    /**
   * @type {any[]}
   */
    let transactions = [];
  
    const API_URL = 'http://localhost/atm';
  
    /**
   * @param {{ payload: { user_id: string; transac_type: any; amount: number; balance_after: number; timestamp: string; }[]; }} data
   */
    async function encryptData(data) {
      try {
        const response = await fetch(`${API_URL}/routes.php?request=encryptdata`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });
        const result = await response.json();
        return result;
      } catch (error) {
        console.error('Encryption error:', error);
        throw error;
      }
    }
  
    /**
   * @param {string} type
   */
    async function performTransaction(type) {
      const numAmount = parseFloat(amount);
      if (!amount || isNaN(numAmount) || numAmount <= 0) {
        message = 'Please enter a valid amount';
        return;
      }
  
      if (type === 'withdraw' && parseFloat(amount) > balance) {
        message = 'Insufficient funds';
        return;
      }
  
      const transactionData = {
        payload: [{
          user_id: userId,
          transac_type: type,
          amount: parseFloat(amount),
          balance_after: type === 'withdraw' ? balance - parseFloat(amount) : balance + parseFloat(amount),
          timestamp: new Date().toISOString()
        }]
      };
  
      try {
        const encryptedData = await encryptData(transactionData);
        const response = await fetch(`${API_URL}/routes.php?request=addtransaction`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: encryptedData
        });
  
        const result = await response.json();
        
        if (result.success) {
          balance = transactionData.payload[0].balance_after;
          message = `${type.charAt(0).toUpperCase() + type.slice(1)} successful`;
          amount = '';
          await loadTransactions();
        } else {
          message = result.error || 'Transaction failed';
        }
      } catch (error) {
        message = 'Error processing transaction';
        console.error(error);
      }
    }
  
    async function loadTransactions() {
      try {
        const response = await fetch(`${API_URL}/routes.php?request=gettransactions/${userId}`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
        });
        const result = await response.json();
        
        if (Array.isArray(result)) {
          transactions = result;
          balance = transactions.reduce((acc, trans) => {
            return trans.transac_type === 'deposit' 
              ? acc + parseFloat(trans.amount) 
              : acc - parseFloat(trans.amount);
          }, 0);
        } else {
          console.error('Invalid transactions data:', result);
          message = 'Error loading transactions';
        }
      } catch (error) {
        console.error('Error loading transactions:', error);
        message = 'Error loading transactions';
      }
    }
  
    function authenticate() {
      if (pin === '1234') {
        isAuthenticated = true;
        loadTransactions();
      } else {
        message = 'Invalid PIN';
      }
    }
  
    onMount(() => {
      if (isAuthenticated) {
        loadTransactions();
      }
    });
  </script>
  
  <main class="container">
    {#if !isAuthenticated}
      <div class="auth-container">
        <h2>Welcome to ATM</h2>
        <input
          type="password"
          bind:value={pin}
          placeholder="Enter PIN"
          maxlength="4"
        />
        <button on:click={authenticate}>Login</button>
      </div>
    {:else}
      <div class="atm-container">
        <h2>ATM Machine</h2>
        <div class="balance">Current Balance: ${balance.toFixed(2)}</div>
        
        <div class="transaction-form">
          <input
            type="number"
            bind:value={amount}
            placeholder="Enter amount"
            min="0"
          />
          <div class="buttons">
            <button on:click={() => performTransaction('deposit')}>Deposit</button>
            <button 
              on:click={() => performTransaction('withdraw')}
              disabled={parseFloat(amount) > balance}
            >
              Withdraw
            </button>
          </div>
        </div>
  
        {#if message}
          <div class="message">{message}</div>
        {/if}
  
        <div class="transactions">
          <h3>Recent Transactions</h3>
          <div class="transaction-list">
            {#each transactions as transaction}
              <div class="transaction-item">
                <span>{transaction.transac_type}</span>
                <span>${transaction.amount.toFixed(2)}</span>
                <span>{new Date(transaction.timestamp).toLocaleString()}</span>
              </div>
            {/each}
          </div>
        </div>
      </div>
    {/if}
  </main>
  
  <style>
    .container {
      max-width: 600px;
      margin: 0 auto;
      padding: 20px;
    }
  
    .auth-container,
    .atm-container {
      background: #f5f5f5;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
  
    .balance {
      font-size: 24px;
      margin: 20px 0;
      padding: 10px;
      background: #e0e0e0;
      border-radius: 4px;
    }
  
    .transaction-form {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-bottom: 20px;
    }
  
    .buttons {
      display: flex;
      gap: 10px;
    }
  
    input {
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
  
    button {
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      background: #007bff;
      color: white;
      cursor: pointer;
    }
  
    button:disabled {
      background: #cccccc;
      cursor: not-allowed;
    }
  
    .message {
      padding: 10px;
      margin: 10px 0;
      border-radius: 4px;
      background: #e3f2fd;
    }
  
    .transactions {
      margin-top: 20px;
    }
  
    .transaction-list {
      max-height: 300px;
      overflow-y: auto;
    }
  
    .transaction-item {
      display: flex;
      justify-content: space-between;
      padding: 10px;
      border-bottom: 1px solid #ddd;
    }
  </style>