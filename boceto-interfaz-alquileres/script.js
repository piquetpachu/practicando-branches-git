// Global state management
const state = {
  currentTab: "dashboard",
  currentTheme: "dark",
  departments: [
    {
      id: 1,
      numero: "101",
      capacidad: 4,
      comodidades: "WiFi, TV, Cocina completa, Aire acondicionado",
      estado: "ocupado",
      tarifa_diaria: 1500,
      inquilino_actual: "Juan Pérez",
    },
    {
      id: 2,
      numero: "102",
      capacidad: 2,
      comodidades: "WiFi, TV, Kitchenette",
      estado: "libre",
      tarifa_diaria: 1200,
      inquilino_actual: null,
    },
    {
      id: 3,
      numero: "201",
      capacidad: 6,
      comodidades: "WiFi, TV, Cocina completa, Aire acondicionado, Balcón",
      estado: "ocupado",
      tarifa_diaria: 2000,
      inquilino_actual: "María García",
    },
    {
      id: 4,
      numero: "202",
      capacidad: 4,
      comodidades: "WiFi, TV, Cocina completa, Aire acondicionado",
      estado: "reservado",
      tarifa_diaria: 1800,
      inquilino_actual: "Carlos López (Reserva)",
    },
  ],
  tenants: [
    {
      id: 1,
      nombre_completo: "Juan Pérez",
      dni: "12345678",
      telefono: "+54 9 11 1234-5678",
      email: "juan.perez@email.com",
      direccion_origen: "Buenos Aires, Argentina",
      marca_vehiculo: "Toyota",
      modelo_vehiculo: "Corolla",
      patente_vehiculo: "ABC123",
      departamento_actual: "101",
      fecha_entrada: "2025-06-01",
      fecha_salida: "2025-06-08",
      estado_pago: "pagado",
    },
    {
      id: 2,
      nombre_completo: "María García",
      dni: "87654321",
      telefono: "+54 9 11 8765-4321",
      email: "maria.garcia@email.com",
      direccion_origen: "Córdoba, Argentina",
      marca_vehiculo: "Ford",
      modelo_vehiculo: "Focus",
      patente_vehiculo: "XYZ789",
      departamento_actual: "201",
      fecha_entrada: "2025-06-03",
      fecha_salida: "2025-06-10",
      estado_pago: "debe",
    },
    {
      id: 3,
      nombre_completo: "Carlos López",
      dni: "11223344",
      telefono: "+54 9 11 1122-3344",
      email: "carlos.lopez@email.com",
      direccion_origen: "Rosario, Argentina",
      marca_vehiculo: "Chevrolet",
      modelo_vehiculo: "Cruze",
      patente_vehiculo: "DEF456",
      departamento_actual: "202",
      fecha_entrada: "2025-06-05",
      fecha_salida: "2025-06-12",
      estado_pago: "parcial",
    },
  ],
  payments: [
    {
      id: 1,
      inquilino: "Juan Pérez",
      departamento: "101",
      fecha_pago: "2025-06-01",
      monto: 7500,
      estado: "pagado",
      forma_pago: "Efectivo",
      comprobante: true,
      periodo: "01/06 - 08/06",
    },
    {
      id: 2,
      inquilino: "María García",
      departamento: "201",
      fecha_pago: "2025-06-03",
      monto: 5000,
      estado: "parcial",
      forma_pago: "Transferencia",
      comprobante: true,
      periodo: "03/06 - 10/06",
      monto_total: 14000,
    },
    {
      id: 3,
      inquilino: "Carlos López",
      departamento: "202",
      fecha_pago: null,
      monto: 0,
      estado: "debe",
      forma_pago: null,
      comprobante: false,
      periodo: "05/06 - 12/06",
      monto_total: 12600,
    },
  ],
  currentDate: new Date(),
  currentPaymentTab: "all",
  editingDepartment: null,
  editingTenant: null,
  editingPayment: null,
}

// Initialize the application
document.addEventListener("DOMContentLoaded", () => {
  initializeApp()
})

function initializeApp() {
  setupEventListeners()
  renderDepartments()
  renderTenants()
  renderPayments()
  renderCalendar()
  loadTheme()
}

// Event Listeners
function setupEventListeners() {
  // Navigation tabs
  document.querySelectorAll(".nav-tab").forEach((tab) => {
    tab.addEventListener("click", function () {
      switchTab(this.dataset.tab)
    })
  })

  // Payment tabs
  document.querySelectorAll(".payment-tab").forEach((tab) => {
    tab.addEventListener("click", function () {
      switchPaymentTab(this.dataset.paymentTab)
    })
  })

  // Modal close on background click
  document.querySelectorAll(".modal").forEach((modal) => {
    modal.addEventListener("click", function (e) {
      if (e.target === this) {
        closeAllModals()
      }
    })
  })

  // Form submissions
  document.getElementById("departmentForm").addEventListener("submit", (e) => {
    e.preventDefault()
    saveDepartment()
  })

  document.getElementById("tenantForm").addEventListener("submit", (e) => {
    e.preventDefault()
    saveTenant()
  })

  document.getElementById("paymentForm").addEventListener("submit", (e) => {
    e.preventDefault()
    savePayment()
  })
}

// Tab Management
function switchTab(tabName) {
  // Update active tab
  document.querySelectorAll(".nav-tab").forEach((tab) => {
    tab.classList.remove("active")
  })
  document.querySelector(`[data-tab="${tabName}"]`).classList.add("active")

  // Update active content
  document.querySelectorAll(".tab-content").forEach((content) => {
    content.classList.remove("active")
  })
  document.getElementById(tabName).classList.add("active")

  state.currentTab = tabName

  // Add fade-in animation
  document.getElementById(tabName).classList.add("fade-in")
  setTimeout(() => {
    document.getElementById(tabName).classList.remove("fade-in")
  }, 300)
}

function switchPaymentTab(tabName) {
  // Update active payment tab
  document.querySelectorAll(".payment-tab").forEach((tab) => {
    tab.classList.remove("active")
  })
  document.querySelector(`[data-payment-tab="${tabName}"]`).classList.add("active")

  state.currentPaymentTab = tabName
  renderPayments()
}

// Theme Management
function toggleTheme() {
  const html = document.documentElement
  const themeIcon = document.querySelector(".theme-toggle i")

  if (html.classList.contains("dark")) {
    html.classList.remove("dark")
    html.classList.add("light")
    themeIcon.className = "fas fa-sun"
    state.currentTheme = "light"
  } else {
    html.classList.remove("light")
    html.classList.add("dark")
    themeIcon.className = "fas fa-moon"
    state.currentTheme = "dark"
  }

  localStorage.setItem("theme", state.currentTheme)
}

function loadTheme() {
  const savedTheme = localStorage.getItem("theme") || "dark"
  const html = document.documentElement
  const themeIcon = document.querySelector(".theme-toggle i")

  if (savedTheme === "light") {
    html.classList.remove("dark")
    html.classList.add("light")
    themeIcon.className = "fas fa-sun"
  } else {
    html.classList.remove("light")
    html.classList.add("dark")
    themeIcon.className = "fas fa-moon"
  }

  state.currentTheme = savedTheme
}

// Department Management
function renderDepartments() {
  renderDepartmentCards()
  renderDepartmentTable()
}

function renderDepartmentCards() {
  const container = document.getElementById("departmentCards")
  container.innerHTML = ""

  state.departments.forEach((dept) => {
    const card = createDepartmentCard(dept)
    container.appendChild(card)
  })
}

function createDepartmentCard(dept) {
  const card = document.createElement("div")
  card.className = "mobile-card"

  const statusBadge = getStatusBadge(dept.estado)

  card.innerHTML = `
        <div class="mobile-card-header">
            <div>
                <div class="mobile-card-title">Departamento ${dept.numero}</div>
                <div class="mobile-card-subtitle">Capacidad: ${dept.capacidad} personas</div>
            </div>
            ${statusBadge}
        </div>
        <div class="mobile-card-content">
            <div class="mobile-card-info">
                <i class="fas fa-dollar-sign"></i>
                <span>Tarifa Diaria: $${dept.tarifa_diaria}</span>
            </div>
            ${
              dept.inquilino_actual
                ? `
                <div class="mobile-card-info">
                    <i class="fas fa-user"></i>
                    <span>${dept.inquilino_actual}</span>
                </div>
            `
                : ""
            }
            <div class="mobile-card-info">
                <i class="fas fa-list"></i>
                <span>${dept.comodidades}</span>
            </div>
            <div class="mobile-card-actions">
                <button class="btn btn-outline btn-sm" onclick="editDepartment(${dept.id})">
                    <i class="fas fa-edit"></i>
                    Editar
                </button>
            </div>
        </div>
    `

  return card
}

function renderDepartmentTable() {
  const tbody = document.getElementById("departmentTableBody")
  tbody.innerHTML = ""

  state.departments.forEach((dept) => {
    const row = document.createElement("tr")
    const statusBadge = getStatusBadge(dept.estado)

    row.innerHTML = `
            <td>${dept.numero}</td>
            <td>${dept.capacidad} personas</td>
            <td>${statusBadge}</td>
            <td>$${dept.tarifa_diaria}</td>
            <td>${dept.inquilino_actual || "-"}</td>
            <td class="truncate" title="${dept.comodidades}">${dept.comodidades}</td>
            <td>
                <button class="btn btn-outline btn-sm" onclick="editDepartment(${dept.id})">
                    <i class="fas fa-edit"></i>
                </button>
            </td>
        `

    tbody.appendChild(row)
  })
}

function getStatusBadge(status) {
  const statusMap = {
    ocupado: "badge-danger",
    libre: "badge-success",
    reservado: "badge-warning",
    mantenimiento: "badge-secondary",
  }

  const statusText = {
    ocupado: "Ocupado",
    libre: "Libre",
    reservado: "Reservado",
    mantenimiento: "Mantenimiento",
  }

  return `<span class="badge ${statusMap[status]}">${statusText[status]}</span>`
}

function openDepartmentModal(dept = null) {
  const modal = document.getElementById("departmentModal")
  const title = document.getElementById("departmentModalTitle")

  if (dept) {
    title.textContent = "Editar Departamento"
    populateDepartmentForm(dept)
    state.editingDepartment = dept
  } else {
    title.textContent = "Agregar Departamento"
    clearDepartmentForm()
    state.editingDepartment = null
  }

  modal.classList.add("active")
}

function closeDepartmentModal() {
  document.getElementById("departmentModal").classList.remove("active")
  state.editingDepartment = null
}

function populateDepartmentForm(dept) {
  document.getElementById("deptNumber").value = dept.numero
  document.getElementById("deptCapacity").value = dept.capacidad
  document.getElementById("deptRate").value = dept.tarifa_diaria
  document.getElementById("deptStatus").value = dept.estado
  document.getElementById("deptAmenities").value = dept.comodidades
}

function clearDepartmentForm() {
  document.getElementById("departmentForm").reset()
}

function editDepartment(id) {
  const dept = state.departments.find((d) => d.id === id)
  if (dept) {
    openDepartmentModal(dept)
  }
}

function saveDepartment() {
  const formData = {
    numero: document.getElementById("deptNumber").value,
    capacidad: Number.parseInt(document.getElementById("deptCapacity").value),
    tarifa_diaria: Number.parseInt(document.getElementById("deptRate").value),
    estado: document.getElementById("deptStatus").value,
    comodidades: document.getElementById("deptAmenities").value,
  }

  if (state.editingDepartment) {
    // Update existing department
    const index = state.departments.findIndex((d) => d.id === state.editingDepartment.id)
    state.departments[index] = { ...state.editingDepartment, ...formData }
  } else {
    // Add new department
    const newDept = {
      id: Date.now(),
      ...formData,
      inquilino_actual: null,
    }
    state.departments.push(newDept)
  }

  renderDepartments()
  closeDepartmentModal()
  showNotification("Departamento guardado exitosamente", "success")
}

// Tenant Management
function renderTenants() {
  renderTenantCards()
  renderTenantTable()
}

function renderTenantCards() {
  const container = document.getElementById("tenantCards")
  container.innerHTML = ""

  state.tenants.forEach((tenant) => {
    const card = createTenantCard(tenant)
    container.appendChild(card)
  })
}

function createTenantCard(tenant) {
  const card = document.createElement("div")
  card.className = "mobile-card"

  const paymentBadge = getPaymentStatusBadge(tenant.estado_pago)

  card.innerHTML = `
        <div class="mobile-card-header">
            <div>
                <div class="mobile-card-title">${tenant.nombre_completo}</div>
                <div class="mobile-card-subtitle">DNI: ${tenant.dni}</div>
            </div>
            ${paymentBadge}
        </div>
        <div class="mobile-card-content">
            <div class="mobile-card-info">
                <i class="fas fa-phone"></i>
                <span>${tenant.telefono}</span>
            </div>
            <div class="mobile-card-info">
                <i class="fas fa-envelope"></i>
                <span>${tenant.email}</span>
            </div>
            <div class="mobile-card-info">
                <i class="fas fa-car"></i>
                <span>${tenant.marca_vehiculo} ${tenant.modelo_vehiculo}</span>
            </div>
            <div class="mobile-card-info">
                <i class="fas fa-building"></i>
                <span>Depto ${tenant.departamento_actual}</span>
            </div>
            <div class="mobile-card-info">
                <i class="fas fa-calendar"></i>
                <span>${tenant.fecha_entrada} - ${tenant.fecha_salida}</span>
            </div>
            <div class="mobile-card-actions">
                <button class="btn btn-outline btn-sm" onclick="viewTenant(${tenant.id})">
                    <i class="fas fa-eye"></i>
                    Ver
                </button>
                <button class="btn btn-outline btn-sm" onclick="editTenant(${tenant.id})">
                    <i class="fas fa-edit"></i>
                    Editar
                </button>
            </div>
        </div>
    `

  return card
}

function renderTenantTable() {
  const tbody = document.getElementById("tenantTableBody")
  tbody.innerHTML = ""

  state.tenants.forEach((tenant) => {
    const row = document.createElement("tr")
    const paymentBadge = getPaymentStatusBadge(tenant.estado_pago)

    row.innerHTML = `
            <td>${tenant.nombre_completo}</td>
            <td>${tenant.dni}</td>
            <td>
                <div>${tenant.telefono}</div>
                <div style="color: var(--text-muted); font-size: 0.875rem;">${tenant.email}</div>
            </td>
            <td>${tenant.departamento_actual}</td>
            <td>
                <div>Entrada: ${tenant.fecha_entrada}</div>
                <div>Salida: ${tenant.fecha_salida}</div>
            </td>
            <td>${paymentBadge}</td>
            <td>
                <div style="display: flex; gap: 0.5rem;">
                    <button class="btn btn-outline btn-sm" onclick="viewTenant(${tenant.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline btn-sm" onclick="editTenant(${tenant.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </td>
        `

    tbody.appendChild(row)
  })
}

function getPaymentStatusBadge(status) {
  const statusMap = {
    pagado: "badge-success",
    debe: "badge-danger",
    parcial: "badge-warning",
  }

  const statusText = {
    pagado: "Pagado",
    debe: "Debe",
    parcial: "Parcial",
  }

  return `<span class="badge ${statusMap[status]}">${statusText[status]}</span>`
}

function openTenantModal(tenant = null) {
  const modal = document.getElementById("tenantModal")
  const title = document.getElementById("tenantModalTitle")

  if (tenant) {
    title.textContent = "Editar Inquilino"
    populateTenantForm(tenant)
    state.editingTenant = tenant
  } else {
    title.textContent = "Nuevo Inquilino"
    clearTenantForm()
    state.editingTenant = null
  }

  modal.classList.add("active")
}

function closeTenantModal() {
  document.getElementById("tenantModal").classList.remove("active")
  state.editingTenant = null
}

function populateTenantForm(tenant) {
  document.getElementById("tenantName").value = tenant.nombre_completo
  document.getElementById("tenantDNI").value = tenant.dni
  document.getElementById("tenantPhone").value = tenant.telefono
  document.getElementById("tenantEmail").value = tenant.email
  document.getElementById("tenantAddress").value = tenant.direccion_origen
  document.getElementById("vehicleBrand").value = tenant.marca_vehiculo
  document.getElementById("vehicleModel").value = tenant.modelo_vehiculo
  document.getElementById("vehiclePlate").value = tenant.patente_vehiculo
  document.getElementById("tenantDepartment").value = tenant.departamento_actual
  document.getElementById("checkInDate").value = tenant.fecha_entrada
  document.getElementById("checkOutDate").value = tenant.fecha_salida
}

function clearTenantForm() {
  document.getElementById("tenantForm").reset()
}

function viewTenant(id) {
  const tenant = state.tenants.find((t) => t.id === id)
  if (tenant) {
    openTenantModal(tenant)
    // Disable all form inputs for view mode
    const inputs = document.querySelectorAll("#tenantForm input, #tenantForm select")
    inputs.forEach((input) => (input.disabled = true))
  }
}

function editTenant(id) {
  const tenant = state.tenants.find((t) => t.id === id)
  if (tenant) {
    openTenantModal(tenant)
    // Enable all form inputs for edit mode
    const inputs = document.querySelectorAll("#tenantForm input, #tenantForm select")
    inputs.forEach((input) => (input.disabled = false))
  }
}

function saveTenant() {
  const formData = {
    nombre_completo: document.getElementById("tenantName").value,
    dni: document.getElementById("tenantDNI").value,
    telefono: document.getElementById("tenantPhone").value,
    email: document.getElementById("tenantEmail").value,
    direccion_origen: document.getElementById("tenantAddress").value,
    marca_vehiculo: document.getElementById("vehicleBrand").value,
    modelo_vehiculo: document.getElementById("vehicleModel").value,
    patente_vehiculo: document.getElementById("vehiclePlate").value,
    departamento_actual: document.getElementById("tenantDepartment").value,
    fecha_entrada: document.getElementById("checkInDate").value,
    fecha_salida: document.getElementById("checkOutDate").value,
  }

  if (state.editingTenant) {
    // Update existing tenant
    const index = state.tenants.findIndex((t) => t.id === state.editingTenant.id)
    state.tenants[index] = { ...state.editingTenant, ...formData }
  } else {
    // Add new tenant
    const newTenant = {
      id: Date.now(),
      ...formData,
      estado_pago: "debe",
    }
    state.tenants.push(newTenant)
  }

  renderTenants()
  closeTenantModal()
  showNotification("Inquilino guardado exitosamente", "success")
}

// Payment Management
function renderPayments() {
  const filteredPayments = getFilteredPayments()
  renderPaymentCards(filteredPayments)
  renderPaymentTable(filteredPayments)
}

function getFilteredPayments() {
  switch (state.currentPaymentTab) {
    case "pending":
      return state.payments.filter((p) => p.estado !== "pagado")
    case "paid":
      return state.payments.filter((p) => p.estado === "pagado")
    default:
      return state.payments
  }
}

function renderPaymentCards(payments) {
  const container = document.getElementById("paymentCards")
  container.innerHTML = ""

  payments.forEach((payment) => {
    const card = createPaymentCard(payment)
    container.appendChild(card)
  })
}

function createPaymentCard(payment) {
  const card = document.createElement("div")
  card.className = "mobile-card"

  const statusBadge = getPaymentStatusBadge(payment.estado)

  card.innerHTML = `
        <div class="mobile-card-header">
            <div>
                <div class="mobile-card-title">${payment.inquilino}</div>
                <div class="mobile-card-subtitle">Depto ${payment.departamento}</div>
            </div>
            ${statusBadge}
        </div>
        <div class="mobile-card-content">
            <div class="mobile-card-info">
                <i class="fas fa-calendar"></i>
                <span>Período: ${payment.periodo}</span>
            </div>
            <div class="mobile-card-info">
                <i class="fas fa-dollar-sign"></i>
                <span>Monto: $${payment.monto.toLocaleString()}</span>
            </div>
            ${
              payment.monto_total
                ? `
                <div class="mobile-card-info">
                    <i class="fas fa-calculator"></i>
                    <span>Total: $${payment.monto_total.toLocaleString()}</span>
                </div>
            `
                : ""
            }
            ${
              payment.fecha_pago
                ? `
                <div class="mobile-card-info">
                    <i class="fas fa-clock"></i>
                    <span>Fecha: ${payment.fecha_pago}</span>
                </div>
            `
                : ""
            }
            <div class="mobile-card-actions">
                <button class="btn btn-outline btn-sm" onclick="viewPayment(${payment.id})">
                    <i class="fas fa-eye"></i>
                    Ver Detalles
                </button>
            </div>
        </div>
    `

  return card
}

function renderPaymentTable(payments) {
  const tbody = document.getElementById("paymentTableBody")
  tbody.innerHTML = ""

  payments.forEach((payment) => {
    const row = document.createElement("tr")
    const statusBadge = getPaymentStatusBadge(payment.estado)

    row.innerHTML = `
            <td>${payment.inquilino}</td>
            <td>${payment.departamento}</td>
            <td>${payment.periodo}</td>
            <td>
                <div style="font-weight: 600;">$${payment.monto.toLocaleString()}</div>
                ${payment.monto_total ? `<div style="font-size: 0.875rem; color: var(--text-muted);">de $${payment.monto_total.toLocaleString()}</div>` : ""}
            </td>
            <td>${payment.fecha_pago || "-"}</td>
            <td>${statusBadge}</td>
            <td>${payment.forma_pago || "-"}</td>
            <td>
                <button class="btn btn-outline btn-sm" onclick="viewPayment(${payment.id})">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        `

    tbody.appendChild(row)
  })
}

function openPaymentModal(payment = null) {
  const modal = document.getElementById("paymentModal")
  const title = document.getElementById("paymentModalTitle")

  if (payment) {
    title.textContent = "Detalles del Pago"
    populatePaymentForm(payment)
    state.editingPayment = payment
  } else {
    title.textContent = "Registrar Nuevo Pago"
    clearPaymentForm()
    state.editingPayment = null
  }

  modal.classList.add("active")
}

function closePaymentModal() {
  document.getElementById("paymentModal").classList.remove("active")
  state.editingPayment = null
}

function populatePaymentForm(payment) {
  document.getElementById("paymentTenant").value = payment.inquilino
  document.getElementById("paymentDepartment").value = payment.departamento
  document.getElementById("paymentAmount").value = payment.monto
  document.getElementById("paymentDate").value = payment.fecha_pago || ""
  document.getElementById("paymentMethod").value = payment.forma_pago || ""
  document.getElementById("paymentStatus").value = payment.estado
}

function clearPaymentForm() {
  document.getElementById("paymentForm").reset()
}

function viewPayment(id) {
  const payment = state.payments.find((p) => p.id === id)
  if (payment) {
    openPaymentModal(payment)
  }
}

function savePayment() {
  const formData = {
    inquilino: document.getElementById("paymentTenant").value,
    departamento: document.getElementById("paymentDepartment").value,
    monto: Number.parseInt(document.getElementById("paymentAmount").value),
    fecha_pago: document.getElementById("paymentDate").value,
    forma_pago: document.getElementById("paymentMethod").value,
    estado: document.getElementById("paymentStatus").value,
  }

  if (state.editingPayment) {
    // Update existing payment
    const index = state.payments.findIndex((p) => p.id === state.editingPayment.id)
    state.payments[index] = { ...state.editingPayment, ...formData }
  } else {
    // Add new payment
    const newPayment = {
      id: Date.now(),
      ...formData,
      comprobante: false,
      periodo: "Nuevo período",
    }
    state.payments.push(newPayment)
  }

  renderPayments()
  closePaymentModal()
  showNotification("Pago guardado exitosamente", "success")
}

// Calendar Management
function renderCalendar() {
  const grid = document.getElementById("calendarGrid")
  const title = document.getElementById("calendarTitle")

  const year = state.currentDate.getFullYear()
  const month = state.currentDate.getMonth()

  const monthNames = [
    "Enero",
    "Febrero",
    "Marzo",
    "Abril",
    "Mayo",
    "Junio",
    "Julio",
    "Agosto",
    "Septiembre",
    "Octubre",
    "Noviembre",
    "Diciembre",
  ]

  title.textContent = `${monthNames[month]} ${year}`

  const daysInMonth = new Date(year, month + 1, 0).getDate()
  const firstDay = new Date(year, month, 1).getDay()

  const departments = ["101", "102", "201", "202"]

  // Mock reservations data
  const reservations = {
    "2025-06-06": [
      { dept: "101", tenant: "Juan Pérez", status: "ocupado" },
      { dept: "201", tenant: "María García", status: "ocupado" },
      { dept: "202", tenant: "Carlos López", status: "reservado" },
    ],
    "2025-06-07": [
      { dept: "101", tenant: "Juan Pérez", status: "ocupado" },
      { dept: "201", tenant: "María García", status: "ocupado" },
      { dept: "202", tenant: "Carlos López", status: "reservado" },
    ],
    "2025-06-08": [
      { dept: "201", tenant: "María García", status: "ocupado" },
      { dept: "202", tenant: "Carlos López", status: "ocupado" },
    ],
    "2025-06-10": [{ dept: "102", tenant: "Ana Martínez", status: "reservado" }],
  }

  grid.innerHTML = ""

  // Header row
  const dayHeader = document.createElement("div")
  dayHeader.className = "calendar-header"
  dayHeader.textContent = "Día"
  grid.appendChild(dayHeader)

  departments.forEach((dept) => {
    const deptHeader = document.createElement("div")
    deptHeader.className = "calendar-header"
    deptHeader.textContent = `Depto ${dept}`
    grid.appendChild(deptHeader)
  })

  // Calendar days
  for (let day = 1; day <= daysInMonth; day++) {
    const dateStr = `${year}-${String(month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`
    const dayReservations = reservations[dateStr] || []

    // Day number
    const dayCell = document.createElement("div")
    dayCell.className = "calendar-day"
    dayCell.textContent = day
    grid.appendChild(dayCell)

    // Department cells
    departments.forEach((dept) => {
      const cell = document.createElement("div")
      cell.className = "calendar-cell"

      const reservation = dayReservations.find((r) => r.dept === dept)
      const status = reservation?.status || "libre"

      const content = document.createElement("div")
      content.className = `calendar-cell-content status-${status}`
      cell.appendChild(content)

      if (reservation) {
        const info = document.createElement("div")
        info.className = "calendar-cell-info"
        info.innerHTML = `
                    <div class="calendar-tenant">${reservation.tenant}</div>
                    <span class="badge badge-secondary" style="font-size: 0.625rem;">${reservation.status}</span>
                `
        cell.appendChild(info)
      }

      grid.appendChild(cell)
    })
  }
}

function navigateMonth(direction) {
  state.currentDate.setMonth(state.currentDate.getMonth() + direction)
  renderCalendar()
}

// Notification Management
function toggleNotifications() {
  const panel = document.getElementById("notificationsPanel")
  panel.classList.toggle("active")
}

function dismissNotification(button) {
  const notification = button.closest(".notification-item")
  notification.style.animation = "slideOut 0.3s ease-out forwards"
  setTimeout(() => {
    notification.remove()
    updateNotificationBadge()
  }, 300)
}

function showNotification(message, type = "info") {
  const panel = document.getElementById("notificationsPanel")
  const notification = document.createElement("div")
  notification.className = `notification-item notification-${type} slide-in`

  const icons = {
    success: "fas fa-check-circle",
    warning: "fas fa-exclamation-triangle",
    error: "fas fa-exclamation-circle",
    info: "fas fa-info-circle",
  }

  notification.innerHTML = `
        <i class="${icons[type]}"></i>
        <div class="notification-content">
            <div class="notification-message">${message}</div>
            <div class="notification-time">Ahora</div>
        </div>
        <button class="notification-close" onclick="dismissNotification(this)">
            <i class="fas fa-times"></i>
        </button>
    `

  panel.appendChild(notification)
  panel.classList.add("active")

  // Auto dismiss after 5 seconds
  setTimeout(() => {
    if (notification.parentNode) {
      dismissNotification(notification.querySelector(".notification-close"))
    }
  }, 5000)

  updateNotificationBadge()
}

function updateNotificationBadge() {
  const badge = document.querySelector(".notification-badge")
  const notifications = document.querySelectorAll(".notification-item")
  badge.textContent = notifications.length

  if (notifications.length === 0) {
    badge.style.display = "none"
  } else {
    badge.style.display = "flex"
  }
}

// Utility Functions
function closeAllModals() {
  document.querySelectorAll(".modal").forEach((modal) => {
    modal.classList.remove("active")
  })
  state.editingDepartment = null
  state.editingTenant = null
  state.editingPayment = null
}

function formatCurrency(amount) {
  return new Intl.NumberFormat("es-AR", {
    style: "currency",
    currency: "ARS",
  }).format(amount)
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString("es-AR")
}

// Add CSS animation for slide out
const style = document.createElement("style")
style.textContent = `
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`
document.head.appendChild(style)

// Initialize notification badge
updateNotificationBadge()
