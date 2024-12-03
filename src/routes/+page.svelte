<script lang="ts">
  import { onMount } from 'svelte';

  let balance = 0;
  let amount = '';
  let pin = '';
  let isAuthenticated = false;
  let message = '';
  let userId = '1'; // Mock user ID for demonstration
  let transactions: any[] = []; 
  
  async function fetchWithDebug(url: string, options: any) {
    try {
      const response = await fetch(url, options);
      if (!response.ok) {
        const errorDetails = await response.text();
        console.error(`API Error: ${response.status} - ${errorDetails}`);
        throw new Error(`API Error: ${response.statusText}`);
      }
      return response.json();
    } catch (error) {
      console.error('Network Error:', error);
      throw error;
    }
  }

  async function encryptData(data: any) {
    try {
      const response = await fetchWithDebug(`/Api/routes.php?request=encryptdata`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });
      return response.encryptedData; // Adjust this based on your backend response format
    } catch (error) {
      message = 'Encryption error';
      console.error('Encryption error:', error);
      throw error;
    }
  }

  async function performTransaction(type: string) {
    try {
      const numAmount = parseFloat(amount);
      if (!amount || isNaN(numAmount) || numAmount <= 0) {
        message = 'Please enter a valid amount';
        return;
      }

      if (type === 'withdraw' && numAmount > balance) {
        message = 'Insufficient funds';
        return;
      }

      const transactionData = {
        payload: [{
          user_id: userId,
          transac_type: type,
          amount: numAmount,
          balance_after: type === 'withdraw' ? balance - numAmount : balance + numAmount,
          timestamp: new Date().toISOString()
        }]
      };

      // First encrypt the data
      const encryptedResponse = await encryptData(transactionData);
      
      // Then send the encrypted data
      const result = await fetchWithDebug(`/Api/routes.php?request=addtransaction`, {
        method: 'POST',
        headers: { 'Content-Type': 'text/plain' }, // Changed to text/plain for encrypted data
        body: encryptedResponse
      });

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
      console.error('Transaction error:', error);
    }
  }

  async function loadTransactions() {
    try {
      const result = await fetchWithDebug(`/Api/routes.php?request=gettransactions/${userId}`, {
        method: 'GET'
      });

      if (Array.isArray(result)) {
        transactions = result;
        balance = result.reduce((acc, trans) => {
          const amount = parseFloat(trans.amount);
          return trans.transac_type === 'deposit' ? acc + amount : acc - amount;
        }, 0);
      } else {
        throw new Error('Invalid transactions data');
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