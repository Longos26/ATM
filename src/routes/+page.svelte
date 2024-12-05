<script lang="ts">
  import { onMount } from 'svelte';

  let balance: number = 0;
  let amount: string = '';
  let pin: string = '';
  let isAuthenticated: boolean = false;
  let message: string = '';
  let userId: string = '1'; // Mock user ID for demonstration
  let transactions: Array<{ transac_type: string, amount: number, timestamp: string }> = [];

  function mockEncryptData(data: any) {
    return JSON.stringify(data);
  }

  function mockFetchWithDebug(url: string, options: RequestInit) {
    return new Promise((resolve, reject) => {
      try {
        if (url.includes('addtransaction')) {
          const transactionData = JSON.parse(options.body as string);
          const storedTransactions = JSON.parse(localStorage.getItem('transactions') || '[]');
          storedTransactions.push(transactionData.payload[0]);
          localStorage.setItem('transactions', JSON.stringify(storedTransactions));
          resolve({ success: true });
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

      // Simulate sending the encrypted data
      await mockFetchWithDebug(`/Api/routes.php?request=addtransaction`, {
        method: 'POST',
        headers: { 'Content-Type': 'text/plain' },
        body: encryptedResponse
      });

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
      } else {
        message = 'Transaction failed';
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
    if (isAuthenticated) {
      loadTransactions();
    }
  });
</script>

<main class="max-w-lg mx-auto p-5">
  {#if !isAuthenticated}
    <div class="bg-gray-100 p-5 rounded-lg shadow-md">
      <h2 class="text-2xl font-bold mb-4">Welcome to ATM</h2>
      <input
        type="password"
        bind:value={pin}
        placeholder="Enter PIN"
        maxlength="4"
        class="w-full p-2 border border-gray-300 rounded mb-4"
      />
      <button class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600" on:click={authenticate}>Login</button>
    </div>
  {:else}
    <div class="bg-gray-100 p-5 rounded-lg shadow-md">
      <h2 class="text-2xl font-bold mb-4">ATM Machine</h2>
      <div class="text-xl font-semibold mb-4">Current Balance: ${balance.toFixed(2)}</div>
      
      <div class="flex flex-col gap-4 mb-4">
        <input
          type="number"
          bind:value={amount}
          placeholder="Enter amount"
          min="0"
          class="w-full p-2 border border-gray-300 rounded"
        />
        <div class="flex gap-4">
          <button class="flex-1 bg-green-500 text-white py-2 rounded hover:bg-green-600" on:click={() => performTransaction('deposit')}>Deposit</button>
          <button 
            class="flex-1 bg-red-500 text-white py-2 rounded hover:bg-red-600 disabled:bg-gray-400"
            on:click={() => performTransaction('withdraw')}
            disabled={parseFloat(amount) > balance}
          >
            Withdraw
          </button>
        </div>
      </div>

      {#if message}
        <div class="p-3 mb-4 rounded bg-blue-100 text-blue-800">{message}</div>
      {/if}

      <div class="mt-4">
        <h3 class="text-lg font-semibold mb-2">Recent Transactions</h3>
        <div class="max-h-72 overflow-y-auto">
          {#each transactions as transaction}
            <div class="flex justify-between p-2 border-b border-gray-200">
              <span>{transaction.transac_type}</span>
              <span>${transaction.amount.toFixed(2)}</span>
              <span>{new Date(transaction.timestamp).toLocaleString()}</span>
            </div>
          {/each}
        </div>
      </div>

      <button class="w-full mt-4 bg-gray-500 text-white py-2 rounded hover:bg-gray-600" on:click={logout}>Exit</button>
    </div>
  {/if}
</main>
