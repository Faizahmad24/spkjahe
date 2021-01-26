<title>Diagnosa - Jahe</title>
<?php
include "../../config/koneksi.php";
switch ($_GET['act']) {

  default:
    if ($_POST['submit']) {
      $arcolor = array('#ffffff', '#cc66ff', '#019AFF', '#00CBFD', '#00FEFE', '#A4F804', '#FFFC00', '#FDCD01', '#FD9A01', '#FB6700');
      date_default_timezone_set("Asia/Jakarta");
      $fnptanggal = date('Y-m-d H:i:s');

      $arbobot = array('0', '1', '0.8', '0.6', '0.4', '0', '-0.4', '-0.6', '-0.8', '-1');
      $argejala = array();

      for ($f = 0; $f < count($_POST['kondisi']); $f++) {
        $arkondisi = explode("_", $_POST['kondisi'][$f]);
        if (strlen($_POST['kondisi'][$f]) > 1) {
          $argejala += array($arkondisi[0] => $arkondisi[1]);
        }
      }
    

      $sqlkondisi = mysqli_query($connection,"SELECT * FROM kondisi order by id+0");
      while ($rkondisi = mysqli_fetch_array($sqlkondisi)) {
        $arkondisitext[$rkondisi['id']] = $rkondisi['kondisi'];
      }

      $sqlpkt = mysqli_query($connection,"SELECT * FROM penyakit order by kode_penyakit+0");
      while ($rpkt = mysqli_fetch_array($sqlpkt)) {
        $arpkt[$rpkt['kode_penyakit']] = $rpkt['nama_penyakit'];
        $ardpkt[$rpkt['kode_penyakit']] = $rpkt['det_penyakit'];
        $arspkt[$rpkt['kode_penyakit']] = $rpkt['srn_penyakit'];
        $argpkt[$rpkt['kode_penyakit']] = $rpkt['gambar'];
      }


// -------- proses menghitung metode certainty factor (CF) ---------
// --------------------- START ------------------------
      $sqlpenyakit = mysqli_query($connection,"SELECT * FROM penyakit order by kode_penyakit");
      $arpenyakit = array();
      while ($rpenyakit = mysqli_fetch_array($sqlpenyakit)) {
        $cftotal_temp = 0;
        $cf = 0;
        $sqlgejala = mysqli_query($connection,"SELECT * FROM basis_pengetahuan where kode_penyakit=$rpenyakit[kode_penyakit]");
        $cflama = 0;
        while ($rgejala = mysqli_fetch_array($sqlgejala)) {
          $arkondisi = explode("_", $_POST['kondisi'][0]);
          $gejala = $arkondisi[0];

          for ($f = 0; $f < count($_POST['kondisi']); $f++) {
            $arkondisi = explode("_", $_POST['kondisi'][$f]);
            $gejala = $arkondisi[0];

            if ($rgejala['kode_gejala'] == $gejala) {
              $cf = ($rgejala['mb'] - $rgejala['md']) * $arbobot[$arkondisi[1]];

              // echo $arbobot[$arkondisi[1]];
              // echo($cf)." cf</br>";

              if (($cf >= 0) && ($cf * $cflama >= 0)) {
                  $cflama = $cflama + ($cf * (1 - $cflama));
                  // $cflama "positif";
              }
              if ($cf * $cflama < 0) {
                 $cflama = ($cflama + $cf) / (1 - Min(abs($cflama), abs($cf)));
              }
              if (($cf < 0) && ($cf * $cflama >= 0)) {
                   $cflama = $cflama + ($cf * (1 + $cflama));
              }

            }
          }
          
        }
        if ($cflama > 0) {
          $arpenyakit += array($rpenyakit[kode_penyakit] => number_format($cflama, 4));
        }
        // else{ 
        //   $arpenyakit += array($rpenyakit[kode_penyakit] => number_format($cflama, 4));
        // }

      }
      // die; exit;

      arsort($arpenyakit);

      $fnpgejala = serialize($argejala);
      $fnppenyakit = serialize($arpenyakit);

      $np1 = 0;
      foreach ($arpenyakit as $key1 => $value1) {
        $np1++;
        $fdpkt1[$np1] = $key1;
        $vlpkt1[$np1] = $value1;
      }

      mysqli_query($connection,"INSERT INTO hasil(
                  tanggal,
                  gejala,
                  penyakit,
                  hasil_id,
                  hasil_nilai
				  ) 
	        VALUES(
                '$fnptanggal',
                '$fnpgejala',
                '$fnppenyakit',
                '$fdpkt1[1]',
                '$vlpkt1[1]'
				)");
// --------------------- END -------------------------

      echo "<div class='content'>
	<h2 class='text text-primary'>Hasil Diagnosa &nbsp;&nbsp;<button id='print' onClick='window.print();' data-toggle='tooltip' data-placement='right' title='Klik tombol ini untuk mencetak hasil diagnosa'><i class='fa fa-print'></i> Cetak</button> </h2>
	          <hr><table class='table table-bordered table-striped diagnosa'> 
          <th width=8%>No</th>
          <th width=10%>Kode</th>
          <th>Gejala yang dialami (keluhan)</th>
          <th width=20%>Pilihan</th>
          </tr>";
      $fg = 0;
      foreach ($argejala as $key => $value) {
        $kondisi = $value;
        $fg++;
        $gejala = $key;
        $sql4 = mysqli_query($connection,"SELECT * FROM gejala where kode_gejala = '$key'");
        $r4 = mysqli_fetch_array($sql4);
        echo '<tr><td>' . $fg . '</td>';
        echo '<td>G' . str_pad($r4[kode_gejala], 3, '0', STR_PAD_LEFT) . '</td>';
        echo '<td><span class="hasil text text-primary">' . $r4[nama_gejala] . "</span></td>";
        echo '<td><span class="kondisipilih" style="color:' . $arcolor[$kondisi] . '">' . $arkondisitext[$kondisi] . "</span></td></tr>";
      }
      $np = 0;
      foreach ($arpenyakit as $key => $value) {
        $np++;
        $fdpkt[$np] = $key;
        $nmpkt[$np] = $arpkt[$key];
        $vlpkt[$np] = $value;
      }
      if ($argpkt[$fdpkt[1]]) {
        $gambar = 'gambar/penyakit/' . $argpkt[$fdpkt[1]];
      } else {
        $gambar = 'gambar/noimage.png';
      }
      echo "</table><div class='well well-small'><img class='card-img-top img-bordered-sm' style='float:right; margin-left:15px;' src='" . $gambar . "' height=200><h3>Hasil Diagnosa</h3>";
      echo "<div class='callout callout-default'>Jenis penyakit yang diderita adalah <b><h3 class='text text-success'>" . $nmpkt[1] . "</b> / " . round($vlpkt[1]*100, 2) . " % (" . $vlpkt[1] . ")<br></h3>";
      echo "</div></div><div class='box box-info box-solid'><div class='box-header with-border'><h3 class='box-title'>Detail</h3></div><div class='box-body'><h4>";
      echo $ardpkt[$fdpkt[1]];
      echo "</h4></div></div>
          <div class='box box-warning box-solid'><div class='box-header with-border'><h3 class='box-title'>Saran</h3></div><div class='box-body'><h4>";
      echo $arspkt[$fdpkt[1]];
      echo "</h4></div></div>
          <div class='box box-danger box-solid'><div class='box-header with-border'><h3 class='box-title'>Kemungkinan lain:</h3></div><div class='box-body'><h4>";
      for ($fpl = 2; $fpl <= count($fdpkt); $fpl++) {
        echo " <h4><i class='fa fa-caret-square-o-right'></i> " . $nmpkt[$fpl] . "</b> / " . round($vlpkt[$fpl]*100, 2) . " % (" . $vlpkt[$fpl] . ")<br></h4>";
      }
      echo "</div></div>
      </div>";
    } else {
      echo "
	 <h2 class='text text-primary'>Diagnosa Penyakit</h2>  <hr>
	 <div class='alert alert-success alert-dismissible'>
                <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
                <h4><i class='icon fa fa-exclamation-triangle'></i>Perhatian !</h4>
                Silahkan memilih gejala sesuai dengan kondisi jahe anda, anda dapat memilih kepastian kondisi jahe dari pasti tidak sampai pasti ya, jika sudah tekan tombol proses (<i class='fa fa-search-plus'></i>)  di bawah untuk melihat hasil.
              </div>
		<form name=text_form method=POST action='diagnosa' >
           <table class='table table-bordered table-striped konsultasi'><tbody class='pilihkondisi'>
           <tr><th>No</th><th>Kode</th><th>Gejala</th><th width='20%'>Pilih Kondisi</th></tr>";

      $sql3 = mysqli_query($connection,"SELECT * FROM gejala order by kode_gejala");
      $f = 0;
      while ($r3 = mysqli_fetch_array($sql3)) {
        $f++;
        echo "<tr><td class=opsi>$f</td>";
        echo "<td class=opsi>G" . str_pad($r3[kode_gejala], 3, '0', STR_PAD_LEFT) . "</td>";
        echo "<td class=gejala>$r3[nama_gejala]</td>";

        echo '<td class="opsi"> <select name="kondisi[]" id="sl' . $f . '" class="opsikondisi"/> <option data-id="0" value="0">Pilih jika sesuai</option>';
        $kondisiii = mysqli_query($connection,"SELECT * FROM kondisi order by id");
        // $s = "SELECT * from kondisi order by id";
        // $q = mysqli_query($s);
        while ($rw = mysqli_fetch_array($kondisiii)) {
          ?>
          <option data-id="<?php echo $rw['id']; ?>" value="<?php echo $r3['kode_gejala'] . '_' . $rw['id']; ?>"><?php echo $rw['kondisi']; ?></option>
          <?php
        }
        echo '</select></td>';
        ?>
        <script type="text/javascript">
          $(document).ready(function () {
            var arcolor = new Array('#ffffff', '#cc66ff', '#019AFF', '#00CBFD', '#00FEFE', '#A4F804', '#FFFC00', '#FDCD01', '#FD9A01', '#FB6700');
            setColor();
            $('.pilihkondisi').on('change', 'tr td select#sl<?php echo $f; ?>', function () {
              setColor();
            });
            function setColor()
            {
              var selectedItem = $('tr td select#sl<?php echo $f; ?> :selected');
              var color = arcolor[selectedItem.data("id")];
              $('tr td select#sl<?php echo $f; ?>.opsikondisi').css('background-color', color);
              console.log(color);
            }
          });
        </script>
        <?php
        echo "</tr>";
      }
      echo "
		  <input class='float' type=submit data-toggle='tooltip' data-placement='top' title='Klik disini untuk melihat hasil diagnosa' name=submit value='&#xf00e;' style='font-family:Arial, FontAwesome'>
          </tbody></table></form>";
    }
    break;
}
?>
