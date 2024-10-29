<?php

namespace Database\Seeders;

use App\Models\data\Team;
use Illuminate\Database\Seeder;

class TeamColorSeeder extends Seeder
{
  public function run()
  {
    $seedData = [
      // EPL
      ['id' => '4dsgumo7d4zupm2ugsvm4zm4d', 'color' => json_encode(['background' => '#BD0510', 'font' => '#FFFFFF'])],
      ['id' => 'c8h9bw1l82s06h77xxrelzhur', 'color' => json_encode(['background' => '#D3171E', 'font' => '#FFFFFF'])],
      ['id' => 'b496gs285it6bheuikox6z9mj', 'color' => json_encode(['background' => '#73103C', 'font' => '#FFFFFF'])],
      ['id' => 'a3nyxabgsqlnqfkeg41m6tnpp', 'color' => json_encode(['background' => '#69A8D8', 'font' => '#FFFFFF'])],
      ['id' => '22doj4sgsocqpxw45h607udje', 'color' => json_encode(['background' => '#FFFFFF', 'font' => '#132257'])],
      ['id' => '6eqit8ye8aomdsrrq0hk3v7gh', 'color' => json_encode(['background' => '#C70101', 'font' => '#FFFFFF'])],
      ['id' => '7vn2i2kd35zuetw6b38gw9jsz', 'color' => json_encode(['background' => '#141413', 'font' => '#FFFFFF'])],
      ['id' => 'e5p0ehyguld7egzhiedpdnc3w', 'color' => json_encode(['background' => '#0850A0', 'font' => '#FFFFFF'])],
      ['id' => '4txjdaqveermfryvbfrr4taf7', 'color' => json_encode(['background' => '#66192C', 'font' => '#FFFFFF'])],
      ['id' => '9q0arba2kbnywth8bkxlhgmdr', 'color' => json_encode(['background' => '#064B95', 'font' => '#FFFFFF'])],
      ['id' => '7yx5dqhhphyvfisohikodajhv', 'color' => json_encode(['background' => '#C00808', 'font' => '#FFFFFF'])],
      ['id' => 'hzqh7z0mdl3v7gwete66syxp',  'color' => json_encode(['background' => '#FFFFFF', 'font' => '#202020'])],
      ['id' => 'b9si1jn1lfxfund69e9ogcu2n', 'color' => json_encode(['background' => '#FDB913', 'font' => '#202020'])],
      ['id' => '1c8m2ko0wxq1asfkuykurdr0y', 'color' => json_encode(['background' => '#0055A5', 'font' => '#FFFFFF'])],
      ['id' => '1pse9ta7a45pi2w2grjim70ge', 'color' => json_encode(['background' => '#AA1818', 'font' => '#FFFFFF'])],
      ['id' => '1qtaiy11gswx327s0vkibf70n', 'color' => json_encode(['background' => '#820000', 'font' => '#FFFFFF'])],
      ['id' => 'ehd2iemqmschhj2ec0vayztzz', 'color' => json_encode(['background' => '#00019E', 'font' => '#FFFFFF'])],
      ['id' => 'aksa492u5hf93giwcn2zt1nzz', 'color' => json_encode(['background' => '#FA4616', 'font' => '#FFFFFF'])],
      ['id' => '64bxxwu2mv2qqlv0monbkj1om', 'color' => json_encode(['background' => '#5E1444', 'font' => '#FFFFFF'])],
      ['id' => 'bws31egwjda253q9lvykgnivo', 'color' => json_encode(['background' => '#E30613', 'font' => '#FFFFFF'])],

      // SEA
      ['id' => '3vo5mpj7catp66nrwwqiuhuup', 'color' => json_encode(['background' => '#011EA0', 'font' => '#FFFFFF'])],
      ['id' => 'bqbbqm98ud8obe45ds9ohgyrd', 'color' => json_encode(['background' => '#FFFFFF', 'font' => '#202020'])],
      ['id' => '9dntj5dioj5ex52yrgwzxrq9l', 'color' => json_encode(['background' => '#E21C2A', 'font' => '#FFFFFF'])],
      ['id' => 'ej5er0oyngdw138yuumwqbyqt', 'color' => json_encode(['background' => '#1A2F48', 'font' => '#FFFFFF'])],
      ['id' => '4raiad4l2j5lkfaz92pk4osb',  'color' => json_encode(['background' => '#502D7F', 'font' => '#FFFFFF'])],
      ['id' => '2tk2l9sgktwc9jhzqdd4mpdtb', 'color' => json_encode(['background' => '#970A2C', 'font' => '#FFFFFF'])],
      ['id' => 'gi0l1habji5hpgar77dl5jqe',  'color' => json_encode(['background' => '#199FD6', 'font' => '#FFFFFF'])],
      ['id' => 'e75syeuawg3ql8nwpi3vr2btz', 'color' => json_encode(['background' => '#2D5CAE', 'font' => '#FFFFFF'])],
      ['id' => 'btcy9nra9ak4m22ovr2ia6m5v', 'color' => json_encode(['background' => '#85D8F8', 'font' => '#FFFFFF'])],
      ['id' => '7gnly6999wao1xarwct4p8fe9', 'color' => json_encode(['background' => '#881F19', 'font' => '#FFFFFF'])],
      ['id' => '67z1gqyiuzpmmb15q5fy7ntc4', 'color' => json_encode(['background' => '#E4022E', 'font' => '#FFFFFF'])],
      ['id' => 'bi1fxjrncd0ram0oi7ja1jyuo', 'color' => json_encode(['background' => '#FFED00', 'font' => '#E2001A'])],
      ['id' => '4kumqzwifv478caxed8zywlh3', 'color' => json_encode(['background' => '#AD1919', 'font' => '#FFFFFF'])],
      ['id' => '5khizzei8z0qbb7exz622wxw3', 'color' => json_encode(['background' => '#FFDD00', 'font' => '#004393'])],
      ['id' => '6tuibxq39fdryu8ou06wcm0q3', 'color' => json_encode(['background' => '#33B65B', 'font' => '#202020'])],
      ['id' => 'ap6blbxhq9elm62vw6tutzlwg', 'color' => json_encode(['background' => '#002F6C', 'font' => '#FFD100'])],
      ['id' => 'dxq76zcvnokq07cszdx0i6kve', 'color' => json_encode(['background' => '#FFFFFF', 'font' => '#202020'])],
      ['id' => '5rwlg5cfv1hu7yf0ek1zxpzy3', 'color' => json_encode(['background' => '#082242', 'font' => '#FFFFFF'])],
      ['id' => '8le3orkfz6iix3jns6g9ojqjg', 'color' => json_encode(['background' => '#00579C', 'font' => '#FFFFFF'])],
      ['id' => '49rkyo4do8uwj06geomw0xr4i', 'color' => json_encode(['background' => '#681a12', 'font' => '#FFFFFF'])],

      // PRD
      ['id' =>  '3kq9cckrnlogidldtdie2fkbl',  'color' => json_encode(['background' =>  '#FFFFFF',  'font' =>  '#202020'])],
      ['id' =>  '7h7eg7q7dbwvzww78h9d5eh0h',  'color' => json_encode(['background' =>  '#CD2534',  'font' =>  '#FFFFFF'])],
      ['id' =>  '4ku8o6uf87yd8iecdalipo6wd',  'color' => json_encode(['background' =>  '#E30613',  'font' =>  '#FFFFFF'])],
      ['id' =>  'agh9ifb2mw3ivjusgedj7c3fe',  'color' => json_encode(['background' =>  '#004D98',  'font' =>  '#EDBB00'])],
      ['id' =>  '3czravw89omgc9o4s0w3l1bg5',  'color' => json_encode(['background' =>  '#EE2523',  'font' =>  '#FFFFFF'])],
      ['id' =>  '63f5h8t5e9qm1fqmvfkb23ghh',  'color' => json_encode(['background' =>  '#0067B1',  'font' =>  '#FFFFFF'])],
      ['id' =>  'ah8dala7suqqkj04n2l8xz4zd',  'color' => json_encode(['background' =>  '#0BB363',  'font' =>  '#FFFFFF'])],
      ['id' =>  '1n1j0wsl763lq7ee1k0c11c02',  'color' => json_encode(['background' =>  '#005999',  'font' =>  '#FFFFFF'])],
      ['id' =>  '1ae3awgwm3i1jds5tfct5441j',  'color' => json_encode(['background' =>  '#FFE400',  'font' =>  '#004B9D'])],
      ['id' =>  'ba5e91hjacvma2sjvixn00pjo',  'color' => json_encode(['background' =>  '#FFFFFF',  'font' =>  '#202020'])],
      ['id' =>  '3budh3j9xivsid3ptm8ptpy4k',  'color' => json_encode(['background' =>  '#FFFFFF',  'font' =>  '#E30613'])],
      ['id' =>  '2l0ldgiwsgb8d6y3z0sfgjzyj',  'color' => json_encode(['background' =>  '#D91A21',  'font' =>  '#FFFFFF'])],
      ['id' =>  '74mcjsm72vr3l9pw2i4qfjchj',  'color' => json_encode(['background' =>  '#FDE607',  'font' =>  '#202020'])],
      ['id' =>  '50x1m4u58lffhq6v6ga1hbxmy',  'color' => json_encode(['background' =>  '#E20613',  'font' =>  '#FFFFFF'])],
      ['id' =>  '10eyb18v5puw4ez03ocaug09m',  'color' => json_encode(['background' =>  '#FFFFFF',  'font' =>  '#E30613'])],
      ['id' =>  '4dtdjgnpdq9uw4sdutti0vaar',  'color' => json_encode(['background' =>  '#004FA3',  'font' =>  '#FFFFFF'])],
      ['id' =>  '82q9159y2u7mkfn3z6hy75r4o',  'color' => json_encode(['background' =>  '#FDE607',  'font' =>  '#0045A7'])],
      ['id' =>  '6f27yvbqcngegwsg2ozxxdj4',   'color' => json_encode(['background' => '#8AC3EE',  'font' => '#FFFFFF'])],
      ['id' =>  '26fes5ubaeq0fk1nay2pj2ob2',  'color' => json_encode(['background' =>  '#A61B2B',  'font' =>  '#FFFFFF'])],
      ['id' =>  'cobffe32r7g4skka9m19qib63',  'color' => json_encode(['background' =>  '#EE1119',  'font' =>  '#FFFFFF'])],

      // LI1
      ['id' => '2b3mar72yy8d6uvat1ka6tn3r', 'color' => json_encode(['background' =>   '#004170',  'font' =>  '#FFFFFF'])],
      ['id' => 'bx0cdmzr2gwr70ez72dorx82p', 'color' => json_encode(['background' =>   '#202020',  'font' =>  '#DC052D'])],
      ['id' => '4t4hod56fsj7utpjdor8so5q6', 'color' => json_encode(['background' =>   '#E51B22',  'font' =>  '#FFFFFF'])],
      ['id' => '3adj8hkws0z1al232bg2h6tk0', 'color' => json_encode(['background' =>   '#ED1C24',  'font' =>  '#FFFFFF'])],
      ['id' => 'be2k34rut1lz79jxenabttqlc', 'color' => json_encode(['background' =>   '#E01E13',  'font' =>  '#FFFFFF'])],
      ['id' => '27xvwccz8kpmqsefjv2b2sc0o', 'color' => json_encode(['background' =>   '#FFFFFF',  'font' =>  '#2FAEE0'])],
      ['id' => '3xedluek08t2ka7oypwuullcn', 'color' => json_encode(['background' =>   '#FFF200',  'font' =>  '#E01E13'])],
      ['id' => '3c3jcs7vc1t6vz5lev162jyv7', 'color' => json_encode(['background' =>   '#EE2223',  'font' =>  '#FFFFFF'])],
      ['id' => '71ajatxnuhc5cnm3xvgdky49w', 'color' => json_encode(['background' =>   '#004996',  'font' =>  '#FFFFFF'])],
      ['id' => 'z1wbqtd0fz5t5eezjvrbld3h',  'color' => json_encode(['background' =>   '#E13327',  'font' => '#FFFFFF'])],
      ['id' => '23pqcgxu1tip265hbe01kszbt', 'color' => json_encode(['background' =>   '#79BCE7',  'font' =>  '#202020'])],
      ['id' => '2cjpmotqo8phcqfpgo9xf4gel', 'color' => json_encode(['background' =>   '#344575',  'font' =>  '#D87043'])],
      ['id' => '750ustvk4jfqmo8q4gnrcxy7z', 'color' => json_encode(['background' =>   '#FCD405',  'font' =>  '#1B8F3A'])],
      ['id' => '2khen2a38l2hkx33s73pehl6o', 'color' => json_encode(['background' =>   '#6E0F12',  'font' =>  '#FFFFFF'])],
      ['id' => '121le8unjfzug3iu9pgkqa1c7', 'color' => json_encode(['background' =>   '#FFFFFF',  'font' =>  '#202020'])],
      ['id' => '4aghw9tyi8ba3ju1ne83gmf9w', 'color' => json_encode(['background' =>   '#3E2C56',  'font' =>  '#FFFFFF'])],
      ['id' => '70nn27vgkt6l48lvv5e66q7ww', 'color' => json_encode(['background' =>   '#F58113',  'font' =>  '#FFFFFF'])],
      ['id' => '17o35s8assv3kamxowzz77k4r', 'color' => json_encode(['background' =>   '#C50C46',  'font' =>  '#FFFFFF'])],

      // BUN
      ['id' =>  '7ad69ngbpjuyzv96drf8d9sn2', 'color' => json_encode(['background' =>  '#202020',  'font' =>  '#DC052D'])],
      ['id' =>  'apoawtpvac4zqlancmvw4nk4o', 'color' => json_encode(['background' =>  '#DC052D',  'font' =>  '#FFFFFF'])],
      ['id' =>  '3dwlvz6cl4lcavvrg0y2dycrt', 'color' => json_encode(['background' =>  '#FFFFFF',  'font' =>  '#202020'])],
      ['id' =>  '9gefq4dz9b2hl8rqrxwrlrzmp', 'color' => json_encode(['background' =>  '#DD013F',  'font' =>  '#FFFFFF'])],
      ['id' =>  'dt4pinj0vw0t0cvz7za6mhmzy', 'color' => json_encode(['background' =>  '#FDE100',  'font' =>  '#202020'])],
      ['id' =>  'c5hderjlkcoaze51e5wgvptk',  'color' => json_encode(['background' =>  '#202020',  'font' =>  '#FFFFFF'])],
      ['id' =>  '4l9mrqzmajz5crzlz50mtbt6x', 'color' => json_encode(['background' =>  '#1961B5',  'font' =>  '#FFFFFF'])],
      ['id' =>  '6k5zscdm9ufw0tguvzyjlp5hq', 'color' => json_encode(['background' =>  '#FD1220',  'font' =>  '#FFFFFF'])],
      ['id' =>  '3xcg7xikgrn2x8oa65sopb2is', 'color' => json_encode(['background' =>  '#FF0000',  'font' =>  '#FFFFFF'])],
      ['id' =>  'a8l3w3n0j99qjlsxj3jnmgkz1', 'color' => json_encode(['background' =>  '#65B32E',  'font' =>  '#FFFFFF'])],
      ['id' =>  'ex3psl8e3ajeypwjy4xfltpx6', 'color' => json_encode(['background' =>  '#FFFFFF',  'font' =>  '#DC052D'])],
      ['id' =>  'go76xxm0xyfgqt1h6tcrtimm',  'color' => json_encode(['background' =>  '#FFFFFF',  'font' =>  '#202020'])],
      ['id' =>  'cu0eztmjcsbydyp53aleznorw', 'color' => json_encode(['background' =>  '#009655',  'font' =>  '#FFFFFF'])],
      ['id' =>  'eq1oq6y61xnzq88zu0cnw131z', 'color' => json_encode(['background' =>  '#005CA9',  'font' =>  '#FFFFFF'])],
      ['id' =>  'a8d2gb2nag8fy0itbuxmcibh2', 'color' => json_encode(['background' =>  '#EB1923',  'font' =>  '#FFFFFF'])],
      ['id' =>  '1c7qokc1j5z50cjj4tcu32haa', 'color' => json_encode(['background' =>  '#C3141E',  'font' =>  '#FFFFFF'])],
      ['id' =>  '8q1ul09cygzswb7tb456bmifv', 'color' => json_encode(['background' =>  '#FFFFFF',  'font' =>  '#ED1C24'])],
      ['id' =>  '1y7e48j8swyafegsucvewse5a', 'color' => json_encode(['background' =>  '#004F9F',  'font' =>  '#FFFFFF'])],
    ];

    foreach ($seedData as $idx => $row) {
      Team::updateOrCreateEx(
        [
          'id' => $row['id'],
        ],
        $row
      );
    }
  }
}
