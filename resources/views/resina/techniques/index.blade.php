<x-app-layout>
    <x-slot name="header">Tecniche</x-slot>

    <div class="max-w-4xl space-y-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Tutto quello che serve partendo da zero, nell'ordine in cui ti servirà. Ogni tecnica vale per qualsiasi progetto:
            cavalieri, supereroi o qualunque altra figura.
        </p>

        {{-- One anchor per technique: step rows link here (t-bsl, t-wash, …). --}}
        <nav class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <a href="#t-prep" class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">Preparare la stampa in resina</a>
            <a href="#t-primer" class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">Il primer a bomboletta</a>
            <a href="#t-tavolozza" class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">La tavolozza bagnata</a>
            <a href="#t-diluire" class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">Diluire e caricare il pennello</a>
            <a href="#t-mischiare" class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">Come si mischiano i colori</a>
            <a href="#t-bsl" class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">Base, ombra e luce</a>
            <a href="#t-tmm" class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">Il sistema True Metallic Metal (TMM)</a>
            <a href="#t-wash" class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">Il wash (lavatura)</a>
            <a href="#t-drybrush" class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">Drybrush (pennello asciutto)</a>
            <a href="#t-velatura" class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">Velatura (glaze)</a>
            <a href="#t-spigoli" class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">Luce sugli spigoli (edge highlight)</a>
            <a href="#t-metallici" class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">Lavorare con i metallici</a>
            <a href="#t-occhi" class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">Gli occhi</a>
            <a href="#t-vernice" class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">Vernice finale</a>
            <a href="#t-errori" class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">Errori tipici di chi inizia</a>
        </nav>

      <article id="t-prep" class="scroll-mt-24 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10"><div class="resina-prose">
        <h3>Preparare la stampa in resina</h3>
        <ol>
          <li>Lava la stampa in alcol isopropilico (IPA) e falla polimerizzare del tutto con la luce UV. Se la resina resta appiccicosa, il colore non attacca.</li>
          <li>Togli i supporti e leviga i segni con carta abrasiva bagnata (grana 400, poi 800). Usa guanti e mascherina: la polvere di resina non va respirata né toccata.</li>
          <li>Lava con acqua tiepida e sapone per piatti, poi lascia asciugare bene.</li>
        </ol>
      </div></article>

      <article id="t-primer" class="scroll-mt-24 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10"><div class="resina-prose">
        <h3>Il primer a bomboletta</h3>
        <ol>
          <li>Agita la bomboletta 1–2 minuti.</li>
          <li>Tieni 20–30 cm di distanza e fai passate brevi, iniziando e finendo fuori dalla figura, con la mano sempre in movimento.</li>
          <li>Meglio due mani leggere che una grossa: una mano spessa copre i dettagli.</li>
          <li>Evita giornate fredde o umide. Lascia asciugare almeno qualche ora, meglio una notte.</li>
        </ol>
        <div class="note"><b>Il tuo primer è nero.</b> È perfetto sotto oro e argento, perché li rende più profondi. I colori chiari (pelle, bianchi, gialli, rosa) invece coprono a fatica: sulle loro zone dai prima una mano di <b>Bianco Osso</b> <span class="code">72.034</span> o <b>Grigio Muraglia</b> <span class="code">72.049</span> diluiti, poi il colore vero.</div>
      </div></article>

      <article id="t-tavolozza" class="scroll-mt-24 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10 grid gap-5 md:grid-cols-[1fr_18rem]"><div class="resina-prose">
        <h3>La tavolozza bagnata</h3>
        <p>Tiene il colore umido per ore. Sul fondo c'è una spugna bagnata (umida, non fradicia), sopra la carta da tavolozza. Se la carta finisce, va bene la carta da forno.</p>
        <ul>
          <li>Se il colore diventa acquoso, la spugna è troppo bagnata: strizzala.</li>
          <li>Se si secca, aggiungi un po' d'acqua sotto la carta.</li>
          <li><b>I metallici non vanno sulla tavolozza bagnata</b>: i brillantini si spargono e sporcano gli altri colori. Usa un piattino di ceramica.</li>
          <li>Chiudi col coperchio a fine sessione: il colore resta usabile anche il giorno dopo.</li>
        </ul>
      </div>
      <figure class="space-y-2"><div class="resina-svg">
        <svg viewBox="0 0 300 180" role="img" aria-label="Strati della tavolozza bagnata">
          <rect x="20" y="90" width="260" height="60" rx="8" fill="#9aa3b5"/>
          <rect x="32" y="96" width="236" height="26" rx="4" fill="#E9C66A"/>
          <rect x="32" y="88" width="236" height="8" rx="2" fill="#fafafa" stroke="#bbb"/>
          <circle cx="80" cy="84" r="9" fill="#c98f6a"/><circle cx="130" cy="84" r="9" fill="#b82d35"/><circle cx="180" cy="84" r="9" fill="#31559a"/>
          <text x="210" y="40">colore</text><line x1="206" y1="44" x2="186" y2="76"/>
          <text x="10" y="40">carta da tavolozza</text><line x1="60" y1="44" x2="60" y2="86"/>
          <text x="85" y="172">spugna umida nel contenitore</text>
        </svg></div>
        <figcaption class="text-xs text-gray-500 dark:text-gray-400">Contenitore, spugna bagnata, carta, colore.</figcaption>
      </figure></article>

      <article id="t-diluire" class="scroll-mt-24 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10 grid gap-5 md:grid-cols-[1fr_18rem]"><div class="resina-prose">
        <h3>Diluire e caricare il pennello</h3>
        <p>La regola più importante: <b>il colore deve essere fluido come latte</b>. Colore denso copre i dettagli e lascia pennellate visibili.</p>
        <ul>
          <li>Metti le gocce sulla tavolozza e aggiungi circa lo stesso volume d'acqua. Per luci e velature, ancora di più.</li>
          <li>Intingi solo la punta, mai fino alla ghiera metallica.</li>
          <li>Togli l'eccesso strisciando il pennello sul bordo della tavolozza, girandolo per rifare la punta.</li>
          <li>Due o tre mani sottili, aspettando che ognuna asciughi. Vedere il nero sotto dopo la prima mano è normale.</li>
          <li>Non lasciare mai il pennello in piedi nell'acqua sulla punta.</li>
        </ul>
      </div>
      <figure class="space-y-2"><div class="resina-svg">
        <svg viewBox="0 0 300 150" role="img" aria-label="Pennello caricato correttamente">
          <rect x="10" y="67" width="150" height="16" rx="8" fill="#6b4b2e"/>
          <rect x="160" y="65" width="40" height="20" fill="#b9bcc4"/>
          <path d="M200 65 Q250 67 285 75 Q250 83 200 85 Z" fill="#e8d9b8"/>
          <path d="M245 68 Q268 71 285 75 Q268 79 245 82 Z" fill="#c98f6a"/>
          <text x="200" y="112">colore solo sulla punta</text>
          <text x="130" y="48">ghiera: niente colore qui</text>
        </svg></div>
        <figcaption class="text-xs text-gray-500 dark:text-gray-400">Il colore sta nel primo terzo delle setole.</figcaption>
      </figure></article>

      <article id="t-mischiare" class="scroll-mt-24 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10"><div class="resina-prose">
        <h3>Come si mischiano i colori</h3>
        <ol>
          <li>Conta le gocce direttamente dal flacone sulla tavolozza: una goccia è una parte. Le ricette scritte "2 gocce + 1 goccia" si possono anche raddoppiare, basta mantenere la proporzione.</li>
          <li>Unisci le gocce con uno stuzzicadenti o un pennello vecchio e mescola finché il colore è uniforme, senza striature.</li>
          <li>Aggiungi l'acqua dopo aver mescolato.</li>
          <li>Prepara un po' più del necessario: rifare esattamente lo stesso colore è difficile.</li>
          <li>Prova il colore su un bordo della tavolozza o su un pezzo di stampa scartato. Asciutto diventa un po' più scuro.</li>
        </ol>
        <div class="note"><b>Nero e blu sono fortissimi.</b> Aggiungi il colore scuro al chiaro una goccia alla volta, mai il contrario. Per le ombre di pelle e colori caldi usa i marroni: il nero rende tutto grigio e sporco.</div>
      </div></article>

      <article id="t-bsl" class="scroll-mt-24 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10 grid gap-5 md:grid-cols-[1fr_18rem]"><div class="resina-prose">
        <h3>Base, ombra e luce</h3>
        <p>Immagina una lampada sopra la testa della figura. Le parti che sporgono e guardano in alto (fronte, naso, zigomi, spalle, bordi delle armature) prendono luce. Le parti sotto e dentro (orbite, sotto il naso, sotto il mento, pieghe, incavi) restano in ombra. Vallejo chiama questo metodo <b>BSL</b>: Base, Shadow, Light.</p>
        <ol>
          <li>Stendi la <b>base</b> ovunque.</li>
          <li>Metti l'<b>ombra</b> negli incavi e nelle zone basse.</li>
          <li>Ripassa la <b>base</b> lasciando l'ombra solo in fondo.</li>
          <li>Metti la <b>luce</b> su un'area più piccola, solo sui rilievi.</li>
          <li>Aggiungi piccoli punti di <b>luce estrema</b> sui punti più alti.</li>
        </ol>
        <p>Ogni strato copre meno superficie del precedente, come una piramide.</p>
      </div>
      <figure class="space-y-2"><div class="resina-svg">
        <svg viewBox="0 0 300 200" role="img" aria-label="Sfera con ombra, base e luce">
          <defs><clipPath id="clipS"><circle cx="110" cy="100" r="80"/></clipPath></defs>
          <circle cx="110" cy="100" r="80" fill="#85513d"/>
          <g clip-path="url(#clipS)"><circle cx="100" cy="85" r="68" fill="#b97857"/><circle cx="92" cy="70" r="42" fill="#c98f6a"/><circle cx="86" cy="58" r="18" fill="#e9c596"/></g>
          <text x="176" y="22">luce dall'alto</text>
          <circle cx="198" cy="58" r="5" fill="#e9c596"/><text x="208" y="62">luce estrema</text>
          <circle cx="198" cy="88" r="5" fill="#c98f6a"/><text x="208" y="92">luce</text>
          <circle cx="198" cy="118" r="5" fill="#b97857"/><text x="208" y="122">base</text>
          <circle cx="198" cy="156" r="5" fill="#85513d"/><text x="208" y="160">ombra</text>
        </svg></div>
        <figcaption class="text-xs text-gray-500 dark:text-gray-400">Qui con i quattro colori del set Tanned Skin.</figcaption>
      </figure></article>

      <article id="t-tmm" class="scroll-mt-24 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10 grid gap-5 md:grid-cols-[1fr_18rem]"><div class="resina-prose">
        <h3>Il sistema True Metallic Metal (TMM)</h3>
        <p>I tuoi due set TMM applicano lo stesso metodo BSL al metallo. Ogni famiglia ha quattro flaconi:</p>
        <ul>
          <li><b>Base</b> (Imperial Gold <span class="code">77.123</span>, Sterling Silver <span class="code">77.121</span>): il metallo principale, su tutta la superficie.</li>
          <li><b>Shade</b> (<span class="code">77.143</span>, <span class="code">77.141</span>): <b>non è un metallo, è un wash</b>. È una versione scura, satinata e trasparente della Base: va negli incavi e lascia vedere il brillante sotto. Hai quindi già i wash per oro e argento.</li>
          <li><b>Light</b> (<span class="code">77.103</span>, <span class="code">77.101</span>): versione più chiara e luminosa, per rilievi e spigoli. Si può mischiare con la Base in varie proporzioni per luci intermedie.</li>
          <li><b>Airbrush</b> (<span class="code">77.163</span>, <span class="code">77.161</span>): la Base già diluita per aerografo. A pennello puoi usarla per mani molto sottili e lisce su superfici grandi, ma copre meno: per iniziare usa la Base normale.</li>
        </ul>
      </div>
      <figure class="space-y-2"><div class="resina-svg">
        <svg viewBox="0 0 300 170" role="img" aria-label="Placca d'oro con Shade, Base e Light">
          <path d="M40 40 Q150 10 260 40 L240 130 Q150 150 60 130 Z" fill="#c89b35"/>
          <path d="M60 128 Q150 146 240 128 L236 112 Q150 128 64 112 Z" fill="#765225" opacity=".85"/>
          <path d="M40 40 Q150 10 260 40" stroke="#efd36a" stroke-width="5" fill="none"/>
          <path d="M90 34 Q150 20 210 34" stroke="#e2e4e3" stroke-width="2" fill="none"/>
          <text x="118" y="164">Shade in basso, Light in alto</text>
        </svg></div>
        <figcaption class="text-xs text-gray-500 dark:text-gray-400">Base su tutto, Shade sotto, Light sui bordi alti.</figcaption>
      </figure></article>

      <article id="t-wash" class="scroll-mt-24 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10 grid gap-5 md:grid-cols-[1fr_18rem]"><div class="resina-prose">
        <h3>Il wash (lavatura)</h3>
        <p>È un colore scuro <b>molto liquido e trasparente</b>. Lo stendi su tutta la zona: scivola via dai rilievi e si deposita da solo negli incavi, nelle fessure e intorno ai dettagli. Crea le ombre in automatico, senza bisogno di saper sfumare. È la tecnica che dà il risultato migliore con il minimo sforzo.</p>
        <ol>
          <li>Aspetta che la base sia asciutta del tutto.</li>
          <li>Carica bene il pennello e stendi il wash sulla zona.</li>
          <li>Se si forma una pozza su una parte piatta, toccala con il pennello asciutto e pulito per assorbire l'eccesso.</li>
          <li>Non ripassare mentre asciuga. Aspetta almeno 30–60 minuti.</li>
          <li>Poi ripassa la base solo sui rilievi, lasciando lo scuro negli incavi.</li>
        </ol>
        <div class="note">Per oro e argento usa gli <b>Shade TMM</b>. Per pelle, cuoio, rocce e stoffe ti serve un wash vero (vedi "Da comprare"), oppure quello fatto in casa: 1 goccia di colore + 6–10 gocce d'acqua + una punta minuscola di sapone per piatti.</div>
      </div>
      <figure class="space-y-2"><div class="resina-svg">
        <svg viewBox="0 0 300 220" role="img" aria-label="Sezione di una superficie prima e dopo il wash">
          <text x="10" y="18">prima</text>
          <path d="M10 70 L60 70 L75 95 L95 95 L110 70 L170 70 L180 90 L195 90 L205 70 L290 70 L290 100 L10 100 Z" fill="#c89b35"/>
          <text x="10" y="128">dopo il wash</text>
          <path d="M10 180 L60 180 L75 205 L95 205 L110 180 L170 180 L180 200 L195 200 L205 180 L290 180 L290 210 L10 210 Z" fill="#c89b35"/>
          <path d="M66 186 L75 205 L95 205 L104 186 Q85 192 66 186 Z" fill="#765225"/>
          <path d="M175 184 L180 200 L195 200 L201 184 Q188 190 175 184 Z" fill="#765225"/>
          <text x="120" y="150">lo scuro resta negli incavi</text>
        </svg></div>
        <figcaption class="text-xs text-gray-500 dark:text-gray-400">Il liquido si raccoglie da solo dove serve l'ombra.</figcaption>
      </figure></article>

      <article id="t-drybrush" class="scroll-mt-24 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10 grid gap-5 md:grid-cols-[1fr_18rem]"><div class="resina-prose">
        <h3>Drybrush (pennello asciutto)</h3>
        <p>Il contrario del wash: un colore chiaro, quasi senza acqua, che tocca <b>solo spigoli e rilievi</b>. Perfetto per rocce, terra, capelli a ciocche, piume e per far risaltare velocemente i bordi delle armature.</p>
        <ol>
          <li>Usa un pennello vecchio o piatto: il drybrush consuma le setole.</li>
          <li>Prendi pochissimo colore, <b>senza acqua</b>.</li>
          <li>Strofina il pennello su un tovagliolo di carta finché quasi non lascia più segno.</li>
          <li>Spazzola la figura con colpi veloci e leggeri, avanti e indietro.</li>
          <li>Ripeti con un colore più chiaro, ancora più leggero.</li>
        </ol>
      </div>
      <figure class="space-y-2"><div class="resina-svg">
        <svg viewBox="0 0 300 150" role="img" aria-label="Drybrush su una superficie rocciosa">
          <path d="M10 120 L40 70 L60 90 L90 45 L120 85 L150 60 L180 95 L215 50 L250 85 L290 65 L290 140 L10 140 Z" fill="#4c5155"/>
          <path d="M34 80 L40 70 L47 78 M84 55 L90 45 L97 55 M145 67 L150 60 L156 68 M209 60 L215 50 L222 60" stroke="#d4d6d2" stroke-width="4" fill="none" stroke-linecap="round"/>
          <text x="10" y="20">il colore resta solo sulle punte</text>
        </svg></div>
        <figcaption class="text-xs text-gray-500 dark:text-gray-400">Poco colore, pennello quasi asciutto, colpi leggeri.</figcaption>
      </figure></article>

      <article id="t-velatura" class="scroll-mt-24 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10"><div class="resina-prose">
        <h3>Velatura (glaze)</h3>
        <p>Colore diluitissimo (1 goccia di colore con 4–6 d'acqua, meglio con Glaze Medium) steso in più passate trasparenti. Non copre: <b>tinge</b>. Serve per:</p>
        <ul>
          <li>il rossore di guance e labbra;</li>
          <li>ammorbidire il passaggio tra ombra e luce;</li>
          <li>colorare il metallo senza perdere il brillante: cloth di bronzo colorate, armature rosse tipo Iron Man, argento azzurrato;</li>
          <li>scaldare l'oro con un velo di Giallo Soleggiato o Arancio Fuoco.</li>
        </ul>
        <p>Il pennello deve essere umido ma non gocciolante: se lascia una pozza, asciugalo un po' sul tovagliolo.</p>
      </div></article>

      <article id="t-spigoli" class="scroll-mt-24 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10 grid gap-5 md:grid-cols-[1fr_18rem]"><div class="resina-prose">
        <h3>Luce sugli spigoli (edge highlight)</h3>
        <p>Una linea sottile di colore chiaro lungo i bordi delle placche. È il tocco finale che fa sembrare le armature metallo vero.</p>
        <ul>
          <li>Colore ben diluito ma non acquoso.</li>
          <li>Appoggia il <b>fianco</b> della punta sul bordo, non la punta dritta.</li>
          <li>Solo sui bordi rivolti verso l'alto o verso chi guarda: non devi farli tutti.</li>
          <li>Se tremi, appoggia i polsi uno contro l'altro o sul tavolo.</li>
        </ul>
      </div>
      <figure class="space-y-2"><div class="resina-svg">
        <svg viewBox="0 0 300 170" role="img" aria-label="Placca con luce sugli spigoli">
          <path d="M40 40 Q150 10 260 40 L240 130 Q150 150 60 130 Z" fill="#aeb5b6"/>
          <path d="M60 50 Q150 25 240 50 L225 118 Q150 134 75 118 Z" fill="#8f9697"/>
          <path d="M40 40 Q150 10 260 40" stroke="#f4f5f4" stroke-width="3" fill="none"/>
          <path d="M260 40 L240 130" stroke="#e2e4e3" stroke-width="2" fill="none"/>
          <circle cx="150" cy="80" r="14" fill="#b82d35"/><circle cx="145" cy="75" r="3" fill="#fff"/>
          <text x="80" y="165">linea chiara sul bordo superiore</text>
        </svg></div>
        <figcaption class="text-xs text-gray-500 dark:text-gray-400">Placca con edge highlight e gemma.</figcaption>
      </figure></article>

      <article id="t-metallici" class="scroll-mt-24 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10"><div class="resina-prose">
        <h3>Lavorare con i metallici</h3>
        <ul>
          <li><b>Agita tantissimo</b> il flacone, anche un minuto: il metallo si deposita sul fondo. Aiuta una pallina d'acciaio o di vetro da 3–5 mm dentro il flacone.</li>
          <li>Diluisci pochissimo: troppa acqua separa i brillantini.</li>
          <li>Usa un pennello dedicato e sciacqualo spesso: i brillantini consumano la punta.</li>
          <li>Si possono mischiare con colori normali (bronzo, rame, argento azzurrato), ma più colore normale metti, meno brilla. Per colorare il metallo è meglio la velatura sopra.</li>
          <li>L'<b>Oro Lucidato</b> <span class="code">72.055</span> e l'<b>Argento</b> <span class="code">72.052</span> del set base vanno bene come prima mano o per i dettagli: così risparmi i TMM, che sono più belli.</li>
        </ul>
      </div></article>

      <article id="t-occhi" class="scroll-mt-24 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10 grid gap-5 md:grid-cols-[1fr_18rem]"><div class="resina-prose">
        <h3>Gli occhi</h3>
        <p>Nelle figure in stile anime gli occhi sono grandi e disegnati, quindi più facili che su una miniatura realistica. Falli subito dopo la base della pelle: se sbagli, ricopri con la pelle e riprovi.</p>
        <ol>
          <li>Contorno e fessura con Marrone Bichos <span class="code">72.043</span> (non nero puro).</li>
          <li>Bianco dell'occhio con Bianco Osso <span class="code">72.034</span>: il bianco puro sembra finto.</li>
          <li>Iride: un punto di colore che tocca la palpebra superiore.</li>
          <li>Pupilla: punto nero al centro dell'iride.</li>
          <li>Facoltativo: un micro punto di Bianco Teschio come riflesso, nella stessa posizione su entrambi gli occhi.</li>
        </ol>
      </div>
      <figure class="space-y-2"><div class="resina-svg">
        <svg viewBox="0 0 300 120" role="img" aria-label="Quattro fasi per dipingere un occhio">
          <g transform="translate(10 30)"><path d="M0 25 Q30 0 60 25 Q30 45 0 25Z" fill="#c98f6a" stroke="#5c3b31" stroke-width="3"/></g>
          <g transform="translate(82 30)"><path d="M0 25 Q30 0 60 25 Q30 45 0 25Z" fill="#ded5b5" stroke="#5c3b31" stroke-width="3"/></g>
          <g transform="translate(154 30)"><path d="M0 25 Q30 0 60 25 Q30 45 0 25Z" fill="#ded5b5" stroke="#5c3b31" stroke-width="3"/><circle cx="30" cy="22" r="10" fill="#4F6FB5"/></g>
          <g transform="translate(226 30)"><path d="M0 25 Q30 0 60 25 Q30 45 0 25Z" fill="#ded5b5" stroke="#5c3b31" stroke-width="3"/><circle cx="30" cy="22" r="10" fill="#4F6FB5"/><circle cx="30" cy="22" r="4.5" fill="#17191c"/><circle cx="34" cy="18" r="2" fill="#fff"/></g>
          <text x="36" y="100">1</text><text x="108" y="100">2</text><text x="180" y="100">3</text><text x="246" y="100">4–5</text>
        </svg></div>
        <figcaption class="text-xs text-gray-500 dark:text-gray-400">Contorno, bianco, iride, pupilla e riflesso.</figcaption>
      </figure></article>

      <article id="t-vernice" class="scroll-mt-24 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10"><div class="resina-prose">
        <h3>Vernice finale</h3>
        <p>Protegge il colore dalle dita e dalla polvere. Attenzione: la <b>vernice opaca spegne i metallici</b>. Su pelle, capelli e tessuti usa vernice opaca; sulle armature vernice lucida o satinata, oppure niente. Dalla solo quando tutto è asciutto da almeno 24 ore.</p>
      </div></article>

      <article id="t-errori" class="scroll-mt-24 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10"><div class="resina-prose">
        <h3>Errori tipici di chi inizia</h3>
        <ul>
          <li>Colore troppo denso: è l'errore numero uno. Diluisci.</li>
          <li>Voler fare tutto in una sera: ogni strato deve asciugare.</li>
          <li>Nero puro per le ombre di pelle e oro: meglio i marroni o lo Shade TMM.</li>
          <li>Iniziare dalla statua grande: prima stampa una testa e un pezzo di armatura di prova.</li>
          <li>Guardare la figura da 5 cm: si giudica da 30–40 cm, come la vedrà chi la guarda in vetrina.</li>
          <li>Non fotografare il lavoro: una foto ravvicinata mostra subito cosa migliorare.</li>
        </ul>
      </div></article>

        <p class="text-xs text-gray-400">
            I colori a schermo sono simulati e indicativi: dipendono dal monitor, dalla luce e da quanto diluisci. Le ricette sono
            ricette pratiche, non formule ufficiali Vallejo: prova sempre la miscela sulla tavolozza prima della figura.
        </p>
    </div>
</x-app-layout>
