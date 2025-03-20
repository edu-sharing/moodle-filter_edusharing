const SESSION_HEADER_NAME = "Authentication-Info"
const RENDER_DATA_PATH = "public/renderdata"
const JOB_INFO_PATH = "public/job"
const H5P_PATH = "public/h5p"
const ASSET_PATH = "public/asset"
const DB_NAME = "Edu-Sharing-Rendering"
const DB_STORE = "SessionIds"
/** @type {IDBDatabase} */
let db

self.addEventListener('install', async function (event) {
  // Skip the 'waiting' lifecycle phase, to go directly from 'installed' to 'activated', even if
  // there are still previous incarnations of this service worker registration active.
  console.log('install')
  event.waitUntil(self.skipWaiting())
});

self.addEventListener('activate', async function (event) {
  console.log('activate')
  clients.claim()
});

self.addEventListener('controllerchange', () => {
  console.log('New service worker activated. Please reload the page.');
});

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting()
  }
});

self.addEventListener('fetch', (event) => {
  const request = event.request
  const url = request.url
  if (url.includes(RENDER_DATA_PATH)) {
    event.respondWith(handleRenderDataRequest(request))
  } else if (url.includes(JOB_INFO_PATH) || url.includes(H5P_PATH) || url.includes(ASSET_PATH)) {
    event.respondWith(handleSessionRequest(request))
  } else {
    event.respondWith(handleStandardRequest(request))
  }
});

/**
 * Function handleSessionRequest
 *
 * @param {Request} request
 * @returns {Promise<Response>}
 */
const handleSessionRequest = async (request) => {
  await getDb().catch(error => {
    console.error(error)
    return handleStandardRequest(request)
  })
  const sessionId = await retrieveSessionIdFromDb()
  if (sessionId === "") {
    return handleStandardRequest(request)
  }
  const modifiedHeaders = new Headers(request.headers)
  modifiedHeaders.set(SESSION_HEADER_NAME, sessionId)
  modifiedHeaders.forEach((h) => {
    console.log(h)
  })
  const modifiedRequest = new Request(request, {
    mode: 'cors', credentials: 'include', headers: modifiedHeaders,
  });
  return await fetch(modifiedRequest)
}

/**
 * Function handleRenderDataRequest
 *
 * @param {Request} request
 * @returns {Promise<Response>}
 */
const handleRenderDataRequest = async (request) => {
  await getDb().catch(error => {
    console.error(error)
    return handleStandardRequest(request)
  })
  const response = await fetch(request)
  const existingSessionId = await retrieveSessionIdFromDb()
  const newSessionId = response.headers.get(SESSION_HEADER_NAME)
  if (newSessionId !== null && newSessionId !== "" && newSessionId !== existingSessionId) {
    await storeSessionIdToDb(newSessionId)
  }
  return response
}

/**
 * Function handleStandardRequest
 *
 * @param {Request} request
 * @returns {Promise<Response>}
 */
const handleStandardRequest = async (request) => {
  return await fetch(request)
}

/**
 * Function getDb
 *
 * @returns {Promise<String>}
 */
const getDb = () => {
  return new Promise((resolve, reject) => {
    if (db !== undefined) {
      resolve("success")
    }
    if (!indexedDB) {
      reject(new Error("IndexedDB is not supported by current browser. Header auth with Edu-Sharing Rendering Service is not available"))
    }
    const request = indexedDB.open(DB_NAME, 1)
    request.error = (event) => {
      console.error(event)
      reject(new Error("Error opening indexedDB"))
    }
    request.onupgradeneeded = () => {
      const newDb = request.result
      newDb.createObjectStore(DB_STORE, {keyPath: "id"})
    }
    request.onsuccess = () => {
      db = request.result
      resolve("success")
    }
  })
}

/**
 * Function storeSessionIdToDb
 *
 * @param {string} sessionId
 */
const storeSessionIdToDb = (sessionId) => {
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(DB_STORE, "readwrite")
    const store = transaction.objectStore(DB_STORE)
    store.put({id: 1, sessionId: sessionId})
    transaction.oncomplete = () => {
      resolve("Indexed DB transaction completed")
    }
    transaction.onerror = (event) => {
      console.error("DB transaction failed. Cannot store session id.")
      console.error(event)
      reject(new Error("Transaction failed"))
    }
    transaction.onabort = (ev) => {
      console.error("DB transaction aborted. Cannot store session id.")
      console.error(ev)
      reject(new Error("Transaction aborted"))
    }
  })
}

/**
 * Function retrieveSessionIdFromDb
 *
 * Gets the stored session id from the database. Returns a promise resolving to the result on success
 * and to an empty string on failure
 *
 * @return {Promise<String>}
 */
const retrieveSessionIdFromDb = () => {
  return new Promise((resolve, reject) => {
    let result = ""
    const transaction = db.transaction(DB_STORE, "readonly")
    const store = transaction.objectStore(DB_STORE)
    const idQuery = store.get(1)
    idQuery.onsuccess = () => {
      result = idQuery.result !== undefined ? idQuery.result.sessionId : ""
    }
    idQuery.onerror = () => {
      console.error("Query by id failed")
    }
    transaction.oncomplete = () => {
      resolve(result)
    }
    transaction.onerror = (event) => {
      console.error("DB transaction failed. Cannot retrieve session id.")
      console.error(event)
      reject(new Error("Transaction failed"))
    }
    transaction.onabort = (ev) => {
      console.error("DB transaction aborted. Cannot retrieve session id.")
      console.error(ev)
      reject(new Error("Transaction aborted"))
    }
  })
}
