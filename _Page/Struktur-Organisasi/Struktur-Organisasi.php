<section class="breadcrumb-section">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                <li class="breadcrumb-item active" aria-current="page">Struktur Organisasi</li>
            </ol>
        </nav>
    </div>
    <div class="container">
        <div class="row mt-3 mb-3">
            <div class="col-12">
                <h2 class="h1 mb-4 title_segment text-light">Strukutr Organisasi</h2>
            </div>
        </div>
    </div>
</section>
<section class="section bg-white p-4">
    <div class="container">
        <div class="row mb-3">
            <div class="col-12 mb-4 text-center">
                <h2>STRUKTUR ORGANISASI</h2>
                <span>RSU EL-SYIFA PERIODE 2025-2026</span>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-12 mb-4">
                <div id="myDiagramDiv" style="width: 100%; height: 600px; background-color: #f7f7f7; border: 1px solid #ddd;">
                    <!-- Diagram Akan Ditampilkan Disini -->
                </div>
            </div>
        </div>
        <div class="row mb-4">
          <div class="col-12">
            <div class="table table-responsive">
              <table class="table table-striped table-hover table-bordered">
                <thead>
                  <tr>
                    <th align="center"><b>#</b></th>
                    <th align="center"><b>Nama</b></th>
                    <th align="center"><b>Jabatan</b></th>
                    <th align="center"><b>NIP</b></th>
                  </tr>
                </thead>
                <tbody>
                  
                </tbody>
              </table>
            </div>
          </div>
        </div>
    </div>
</section>

<script>
  function initDiagram() {
    const $ = go.GraphObject.make;
    const diagram = $(go.Diagram, "myDiagramDiv", {
      layout: $(go.TreeLayout, { angle: 90, layerSpacing: 40 }),
      "undoManager.isEnabled": true
    });

    // Base URL dinamis sesuai lokasi index.php
    const baseURL = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);

    diagram.nodeTemplate =
      $(go.Node, "Auto",
        $(go.Shape, "RoundedRectangle", { fill: "white", stroke: "#333" }),
        $(go.Panel, "Vertical", { margin: 5 },
          $(go.Picture, {
              desiredSize: new go.Size(80, 80),
              margin: 4,
              imageStretch: go.GraphObject.UniformToFill,
              background: "#eee"
            },
            new go.Binding("source", "foto", function(foto) {
              if (!foto) return baseURL + "assets/img/No-Image.png";
              return baseURL + foto;
            })
          ),
          $(go.TextBlock, { font: "bold 14px sans-serif", margin: 2 },
            new go.Binding("text", "nama")
          ),
          $(go.TextBlock, { font: "12px sans-serif", margin: 2 },
            new go.Binding("text", "jabatan")
          )
        )
      );

    fetch('_Page/Struktur-Organisasi/data.json')
      .then(res => res.json())
      .then(data => {
        console.log("📷 Foto pertama:", data[0]?.foto);
        diagram.model = new go.TreeModel(data);
      })
      .catch(err => console.error("❌ Gagal memuat data JSON:", err));
  }

  window.addEventListener('DOMContentLoaded', initDiagram);
</script>


