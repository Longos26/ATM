<script lang="ts">
  import { onMount } from 'svelte';

  let balance: number = 0;
  let amount: string = '';
  let pin: string = '';
  let isAuthenticated: boolean = false;
  let message: string = '';
  let userId: string = '1'; // Mock user ID for demonstration
  let transactions: Array<{ transac_type: string, amount: number, timestamp: string }> = [];
  let exchangeRates: Record<string, number> = {};
  let selectedCurrency: string = 'USD';
  let convertedBalance: number = 0;

  const currencies = ['USD', 'EUR', 'GBP', 'JPY'];

  function mockEncryptData(data: any) {
    return JSON.stringify(data);
  }

  function mockFetchWithDebug(url: string, options: RequestInit) {
    return new Promise((resolve, reject) => {
      try {
        if (url.includes('addtransaction')) {
          const transactionData = JSON.parse(options.body as string);
          const storedTransactions = JSON.parse(localStorage.getItem('transactions') || '[]');
          const isDuplicate = storedTransactions.some((trans: any) => trans.timestamp === transactionData.payload[0].timestamp);
          if (!isDuplicate) {
            storedTransactions.push(transactionData.payload[0]);
            localStorage.setItem('transactions', JSON.stringify(storedTransactions));
          }
          resolve({ success: !isDuplicate });
        } else if (url.includes('gettransactions')) {
          const storedTransactions = JSON.parse(localStorage.getItem('transactions') || '[]');
          resolve(storedTransactions);
        } else {
          reject('Invalid request');
        }
      } catch (error) {
        reject(error);
      }
    });
  }

  async function fetchExchangeRates() {
    try {
      const response = await fetch('https://api.exchangerate-api.com/v4/latest/PHP');
      const data = await response.json();
      exchangeRates = data.rates;
      convertBalance();
    } catch (error) {
      console.error('Error fetching exchange rates:', error);
    }
  }

  function convertBalance() {
    if (exchangeRates[selectedCurrency]) {
      convertedBalance = balance * exchangeRates[selectedCurrency];
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

      const encryptedResponse = mockEncryptData(transactionData);

      const result = await mockFetchWithDebug(`/Api/routes.php?request=addtransaction`, {
        method: 'POST',
        headers: { 'Content-Type': 'text/plain' },
        body: encryptedResponse
      }) as { success: boolean };

      if (result.success) {
        balance = transactionData.payload[0].balance_after;
        message = `${type.charAt(0).toUpperCase() + type.slice(1)} successful`;
        amount = '';
        await loadTransactions();
        convertBalance();
      } else {
        message = 'Transaction already exists';
      }
    } catch (error) {
      message = 'Error processing transaction';
      console.error('Transaction error:', error);
    }
  }

  async function loadTransactions() {
    try {
      const result = await mockFetchWithDebug(`/Api/routes.php?request=gettransactions/${userId}`, {
        method: 'GET'
      });

      if (Array.isArray(result)) {
        transactions = result.map(trans => ({
          ...trans,
          amount: parseFloat(trans.amount)
        }));
        balance = transactions.reduce((acc, trans) => {
          return trans.transac_type === 'deposit' ? acc + trans.amount : acc - trans.amount;
        }, 0);
        convertBalance();
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

  function logout() {
    isAuthenticated = false;
    pin = '';
    message = 'Logged out successfully';
  }

  onMount(() => {
    fetchExchangeRates();
    if (isAuthenticated) {
      loadTransactions();
    }
  });
</script>

<main class="max-w-lg mx-auto p-6">
  {#if !isAuthenticated}
    <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
      <h2 class="text-3xl font-bold text-center mb-6">BDO ATM</h2>
      <input
        type="password"
        bind:value={pin}
        placeholder="Enter PIN"
        maxlength="4"
        class="w-full p-4 border border-gray-300 rounded-lg text-center mb-6"
      />
      <button class="w-full bg-blue-500 text-white py-3 rounded-lg hover:bg-blue-600 transition" on:click={authenticate}>Login</button>
    </div>
  {:else}
    <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
      <h2 class="text-3xl font-bold text-center mb-6">BDO</h2>
      <div class="flex justify-between items-center mb-6">
        <div class="text-xl font-semibold">Current Balance: ₱{balance.toFixed(2)}</div>
        <select bind:value={selectedCurrency} on:change={convertBalance} class="p-3 border border-gray-300 rounded-lg">
          {#each currencies as currency}
            <option value={currency}>{currency}</option>
          {/each}
        </select>
      </div>
      <div class="text-xl font-semibold mb-6">Converted Balance: {convertedBalance.toFixed(2)} {selectedCurrency}</div>
      
      <div class="flex flex-col gap-6 mb-6">
        <input
          type="number"
          bind:value={amount}
          placeholder="Enter amount"
          min="0"
          class="w-full p-4 border border-gray-300 rounded-lg text-center"
        />
        <div class="flex gap-4">
          <button class="flex-1 bg-green-500 text-white py-3 rounded-lg hover:bg-green-600 transition" on:click={() => performTransaction('deposit')}>Deposit</button>
          <button
            class="flex-1 bg-red-500 text-white py-3 rounded-lg hover:bg-red-600 disabled:bg-gray-400 transition"
            on:click={() => performTransaction('withdraw')}
            disabled={parseFloat(amount) > balance}
          >
            Withdraw
          </button>
        </div>
      </div>
  
      {#if message}
        <div class="p-4 mb-6 rounded bg-blue-100 text-blue-800">{message}</div>
      {/if}
  
      <div class="mt-4">
        <h3 class="text-lg font-semibold mb-4">Recent Transactions</h3>
        <div class="max-h-72 overflow-y-auto">
          {#each transactions as transaction}
            <div class="flex justify-between items-center p-3 border-b border-gray-200">
              <div>
                <span>{transaction.transac_type}</span>
                <span>₱{transaction.amount.toFixed(2)}</span>
                <span>{new Date(transaction.timestamp).toLocaleString()}</span>
              </div>
            </div>
          {/each}
        </div>
      </div>

      <button class="w-full mt-6 bg-gray-500 text-white py-3 rounded-lg hover:bg-gray-600 transition" on:click={logout}>Exit</button>
    </div>
  {/if}
</main>
