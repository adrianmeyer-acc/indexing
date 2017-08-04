OPTIONS (SKIP=1,DIRECT=TRUE,rows=1000)
load data
CHARACTERSET UTF8
infile 'behindthename-synonyms.csv' "str '\n'"
replace
into table PX.IMPORT_GN_SYNONYM
fields terminated by ','
trailing nullcols
           ( GNAME CHAR(4000),
             ISMALE CHAR(4000),
             ISFEMALE CHAR(4000),
             SYN_1 CHAR(4000),
             SYN_2 CHAR(4000),
             SYN_3 CHAR(4000),
             SYN_4 CHAR(4000),
             SYN_5 CHAR(4000),
             SYN_6 CHAR(4000),
             SYN_7 CHAR(4000),
             SYN_8 CHAR(4000),
             SYN_9 CHAR(4000),
             SYN_10 CHAR(4000),
             SYN_11 CHAR(4000),
             SYN_12 CHAR(4000),
             SYN_13 CHAR(4000),
             SYN_14 CHAR(4000),
             SYN_15 CHAR(4000),
             SYN_16 CHAR(4000),
             SYN_17 CHAR(4000),
             SYN_18 CHAR(4000),
             SYN_19 CHAR(4000),
             SYN_20 CHAR(4000),
             SYN_21 CHAR(4000),
             SYN_22 CHAR(4000),
             SYN_23 CHAR(4000),
             SYN_24 CHAR(4000),
             SYN_25 CHAR(4000),
             SYN_26 CHAR(4000),
             SYN_27 CHAR(4000),
             SYN_28 CHAR(4000),
             SYN_29 CHAR(4000),
             SYN_30 CHAR(4000),
             SYN_31 CHAR(4000),
             SYN_32 CHAR(4000),
             SYN_33 CHAR(4000),
             SYN_34 CHAR(4000),
             SYN_35 CHAR(4000),
             SYN_36 CHAR(4000),
             SYN_37 CHAR(4000),
             SYN_38 CHAR(4000),
             SYN_39 CHAR(4000),
             SYN_40 CHAR(4000),
             SYN_41 CHAR(4000),
             SYN_42 CHAR(4000),
             SYN_43 CHAR(4000),
             SYN_44 CHAR(4000),
             SYN_45 CHAR(4000),
             SYN_46 CHAR(4000),
             SYN_47 CHAR(4000),
             SYN_48 CHAR(4000),
             SYN_49 CHAR(4000),
             SYN_50 CHAR(4000),
             SYN_51 CHAR(4000),
             SYN_52 CHAR(4000),
             SYN_53 CHAR(4000),
             SYN_54 CHAR(4000),
             SYN_55 CHAR(4000),
             SYN_56 CHAR(4000),
             SYN_57 CHAR(4000),
             SYN_58 CHAR(4000),
             SYN_59 CHAR(4000),
             SYN_60 CHAR(4000),
             SYN_61 CHAR(4000),
             SYN_62 CHAR(4000),
             SYN_63 CHAR(4000),
             SYN_64 CHAR(4000),
             SYN_65 CHAR(4000),
             SYN_66 CHAR(4000),
             SYN_67 CHAR(4000),
             SYN_68 CHAR(4000),
             SYN_69 CHAR(4000),
             SYN_70 CHAR(4000),
             SYN_71 CHAR(4000),
             SYN_72 CHAR(4000),
             SYN_73 CHAR(4000),
             SYN_74 CHAR(4000),
             SYN_75 CHAR(4000),
             SYN_76 CHAR(4000),
             SYN_77 CHAR(4000),
             SYN_78 CHAR(4000),
             SYN_79 CHAR(4000),
             SYN_80 CHAR(4000),
             SYN_81 CHAR(4000),
             SYN_82 CHAR(4000),
             SYN_83 CHAR(4000),
             SYN_84 CHAR(4000),
             SYN_85 CHAR(4000),
             SYN_86 CHAR(4000),
             SYN_87 CHAR(4000),
             SYN_88 CHAR(4000),
             SYN_89 CHAR(4000),
             SYN_90 CHAR(4000),
             SYN_91 CHAR(4000),
             SYN_92 CHAR(4000),
             SYN_93 CHAR(4000),
             SYN_94 CHAR(4000),
             SYN_95 CHAR(4000),
             SYN_96 CHAR(4000),
             SYN_97 CHAR(4000),
             SYN_98 CHAR(4000),
             SYN_99 CHAR(4000),
             SYN_100 CHAR(4000),
             SYN_101 CHAR(4000),
             SYN_102 CHAR(4000),
             SYN_103 CHAR(4000),
             SYN_104 CHAR(4000),
             SYN_105 CHAR(4000),
             SYN_106 CHAR(4000),
             SYN_107 CHAR(4000),
             SYN_108 CHAR(4000),
             SYN_109 CHAR(4000),
             SYN_110 CHAR(4000),
             SYN_111 CHAR(4000),
             SYN_112 CHAR(4000),
             SYN_113 CHAR(4000),
             SYN_114 CHAR(4000),
             SYN_115 CHAR(4000),
             SYN_116 CHAR(4000),
             SYN_117 CHAR(4000),
             SYN_118 CHAR(4000),
             SYN_119 CHAR(4000),
             SYN_120 CHAR(4000),
             SYN_121 CHAR(4000),
             SYN_122 CHAR(4000),
             SYN_123 CHAR(4000),
             SYN_124 CHAR(4000),
             SYN_125 CHAR(4000),
             SYN_126 CHAR(4000),
             SYN_127 CHAR(4000),
             SYN_128 CHAR(4000),
             SYN_129 CHAR(4000),
             SYN_130 CHAR(4000),
             SYN_131 CHAR(4000),
             SYN_132 CHAR(4000),
             SYN_133 CHAR(4000),
             SYN_134 CHAR(4000),
             SYN_135 CHAR(4000),
             SYN_136 CHAR(4000),
             SYN_137 CHAR(4000),
             SYN_138 CHAR(4000),
             SYN_139 CHAR(4000),
             SYN_140 CHAR(4000),
             SYN_141 CHAR(4000),
             SYN_142 CHAR(4000),
             SYN_143 CHAR(4000),
             SYN_144 CHAR(4000),
             SYN_145 CHAR(4000),
             SYN_146 CHAR(4000),
             SYN_147 CHAR(4000),
             SYN_148 CHAR(4000),
             SYN_149 CHAR(4000),
             SYN_150 CHAR(4000),
             SYN_151 CHAR(4000),
             SYN_152 CHAR(4000),
             SYN_153 CHAR(4000),
             SYN_154 CHAR(4000),
             SYN_155 CHAR(4000),
             SYN_156 CHAR(4000),
             SYN_157 CHAR(4000),
             SYN_158 CHAR(4000),
             SYN_159 CHAR(4000),
             SYN_160 CHAR(4000),
             SYN_161 CHAR(4000),
             SYN_162 CHAR(4000),
             SYN_163 CHAR(4000),
             SYN_164 CHAR(4000),
             SYN_165 CHAR(4000),
             SYN_166 CHAR(4000),
             SYN_167 CHAR(4000),
             SYN_168 CHAR(4000),
             SYN_169 CHAR(4000),
             SYN_170 CHAR(4000),
             SYN_171 CHAR(4000),
             SYN_172 CHAR(4000),
             SYN_173 CHAR(4000),
             SYN_174 CHAR(4000),
             SYN_175 CHAR(4000),
             SYN_176 CHAR(4000),
             SYN_177 CHAR(4000),
             SYN_178 CHAR(4000),
             SYN_179 CHAR(4000),
             SYN_180 CHAR(4000),
             SYN_181 CHAR(4000),
             SYN_182 CHAR(4000),
             SYN_183 CHAR(4000),
             SYN_184 CHAR(4000),
             SYN_185 CHAR(4000),
             SYN_186 CHAR(4000),
             SYN_187 CHAR(4000),
             SYN_188 CHAR(4000),
             SYN_189 CHAR(4000),
             SYN_190 CHAR(4000),
             SYN_191 CHAR(4000),
             SYN_192 CHAR(4000),
             SYN_193 CHAR(4000),
             SYN_194 CHAR(4000),
             SYN_195 CHAR(4000),
             SYN_196 CHAR(4000),
             SYN_197 CHAR(4000),
             SYN_198 CHAR(4000),
             SYN_199 CHAR(4000),
             SYN_200 CHAR(4000),
             SYN_201 CHAR(4000),
             SYN_202 CHAR(4000),
             SYN_203 CHAR(4000),
             SYN_204 CHAR(4000),
             SYN_205 CHAR(4000),
             SYN_206 CHAR(4000),
             SYN_207 CHAR(4000),
             SYN_208 CHAR(4000),
             SYN_209 CHAR(4000),
             SYN_210 CHAR(4000),
             SYN_211 CHAR(4000),
             SYN_212 CHAR(4000),
             SYN_213 CHAR(4000),
             SYN_214 CHAR(4000),
             SYN_215 CHAR(4000),
             SYN_216 CHAR(4000),
             SYN_217 CHAR(4000),
             SYN_218 CHAR(4000),
             SYN_219 CHAR(4000),
             SYN_220 CHAR(4000),
             SYN_221 CHAR(4000),
             SYN_222 CHAR(4000),
             SYN_223 CHAR(4000),
             SYN_224 CHAR(4000),
             SYN_225 CHAR(4000),
             SYN_226 CHAR(4000),
             SYN_227 CHAR(4000),
             SYN_228 CHAR(4000),
             SYN_229 CHAR(4000),
             SYN_230 CHAR(4000),
             SYN_231 CHAR(4000),
             SYN_232 CHAR(4000),
             SYN_233 CHAR(4000),
             SYN_234 CHAR(4000),
             SYN_235 CHAR(4000),
             SYN_236 CHAR(4000),
             SYN_237 CHAR(4000),
             SYN_238 CHAR(4000),
             SYN_239 CHAR(4000),
             SYN_240 CHAR(4000),
             SYN_241 CHAR(4000),
             SYN_242 CHAR(4000),
             SYN_243 CHAR(4000),
             SYN_244 CHAR(4000),
             SYN_245 CHAR(4000),
             SYN_246 CHAR(4000),
             SYN_247 CHAR(4000),
             SYN_248 CHAR(4000),
             SYN_249 CHAR(4000),
             SYN_250 CHAR(4000),
             SYN_251 CHAR(4000),
             SYN_252 CHAR(4000),
             SYN_253 CHAR(4000),
             SYN_254 CHAR(4000),
             SYN_255 CHAR(4000),
             SYN_256 CHAR(4000),
             SYN_257 CHAR(4000),
             SYN_258 CHAR(4000),
             SYN_259 CHAR(4000),
             SYN_260 CHAR(4000),
             SYN_261 CHAR(4000),
             SYN_262 CHAR(4000),
             SYN_263 CHAR(4000),
             SYN_264 CHAR(4000),
             SYN_265 CHAR(4000),
             SYN_266 CHAR(4000),
             SYN_267 CHAR(4000),
             SYN_268 CHAR(4000),
             SYN_269 CHAR(4000),
             SYN_270 CHAR(4000),
             SYN_271 CHAR(4000),
             SYN_272 CHAR(4000),
             SYN_273 CHAR(4000),
             SYN_274 CHAR(4000),
             SYN_275 CHAR(4000),
             SYN_276 CHAR(4000),
             SYN_277 CHAR(4000),
             SYN_278 CHAR(4000),
             SYN_279 CHAR(4000),
             SYN_280 CHAR(4000),
             SYN_281 CHAR(4000),
             SYN_282 CHAR(4000),
             SYN_283 CHAR(4000),
             SYN_284 CHAR(4000),
             SYN_285 CHAR(4000),
             SYN_286 CHAR(4000),
             SYN_287 CHAR(4000),
             SYN_288 CHAR(4000),
             SYN_289 CHAR(4000),
             SYN_290 CHAR(4000),
             SYN_291 CHAR(4000),
             SYN_292 CHAR(4000),
             SYN_293 CHAR(4000),
             SYN_294 CHAR(4000),
             SYN_295 CHAR(4000),
             SYN_296 CHAR(4000),
             SYN_297 CHAR(4000),
             SYN_298 CHAR(4000),
             SYN_299 CHAR(4000),
             SYN_300 CHAR(4000),
             SYN_301 CHAR(4000),
             SYN_302 CHAR(4000),
             SYN_303 CHAR(4000),
             SYN_304 CHAR(4000),
             SYN_305 CHAR(4000),
             SYN_306 CHAR(4000),
             SYN_307 CHAR(4000),
             SYN_308 CHAR(4000),
             SYN_309 CHAR(4000),
             SYN_310 CHAR(4000),
             SYN_311 CHAR(4000),
             SYN_312 CHAR(4000),
             SYN_313 CHAR(4000),
             SYN_314 CHAR(4000),
             SYN_315 CHAR(4000),
             SYN_316 CHAR(4000),
             SYN_317 CHAR(4000),
             SYN_318 CHAR(4000),
             SYN_319 CHAR(4000),
             SYN_320 CHAR(4000),
             SYN_321 CHAR(4000),
             SYN_322 CHAR(4000),
             SYN_323 CHAR(4000),
             SYN_324 CHAR(4000),
             SYN_325 CHAR(4000),
             SYN_326 CHAR(4000),
             SYN_327 CHAR(4000),
             SYN_328 CHAR(4000),
             SYN_329 CHAR(4000),
             SYN_330 CHAR(4000),
             SYN_331 CHAR(4000),
             SYN_332 CHAR(4000),
             SYN_333 CHAR(4000),
             SYN_334 CHAR(4000),
             SYN_335 CHAR(4000),
             SYN_336 CHAR(4000),
             SYN_337 CHAR(4000),
             SYN_338 CHAR(4000),
             SYN_339 CHAR(4000),
             SYN_340 CHAR(4000),
             SYN_341 CHAR(4000),
             SYN_342 CHAR(4000),
             SYN_343 CHAR(4000),
             SYN_344 CHAR(4000),
             SYN_345 CHAR(4000),
             SYN_346 CHAR(4000),
             SYN_347 CHAR(4000),
             SYN_348 CHAR(4000),
             SYN_349 CHAR(4000),
             SYN_350 CHAR(4000),
             SYN_351 CHAR(4000),
             SYN_352 CHAR(4000),
             SYN_353 CHAR(4000),
             SYN_354 CHAR(4000),
             SYN_355 CHAR(4000),
             SYN_356 CHAR(4000),
             SYN_357 CHAR(4000),
             SYN_358 CHAR(4000),
             SYN_359 CHAR(4000),
             SYN_360 CHAR(4000),
             SYN_361 CHAR(4000),
             SYN_362 CHAR(4000),
             SYN_363 CHAR(4000),
             SYN_364 CHAR(4000),
             SYN_365 CHAR(4000),
             SYN_366 CHAR(4000),
             SYN_367 CHAR(4000),
             SYN_368 CHAR(4000),
             SYN_369 CHAR(4000),
             SYN_370 CHAR(4000),
             SYN_371 CHAR(4000),
             SYN_372 CHAR(4000),
             SYN_373 CHAR(4000),
             SYN_374 CHAR(4000),
             SYN_375 CHAR(4000),
             SYN_376 CHAR(4000),
             SYN_377 CHAR(4000),
             SYN_378 CHAR(4000),
             SYN_379 CHAR(4000),
             SYN_380 CHAR(4000),
             SYN_381 CHAR(4000),
             SYN_382 CHAR(4000),
             SYN_383 CHAR(4000),
             SYN_384 CHAR(4000),
             SYN_385 CHAR(4000),
             SYN_386 CHAR(4000),
             SYN_387 CHAR(4000),
             SYN_388 CHAR(4000),
             SYN_389 CHAR(4000),
             SYN_390 CHAR(4000),
             SYN_391 CHAR(4000),
             SYN_392 CHAR(4000),
             SYN_393 CHAR(4000),
             SYN_394 CHAR(4000),
             SYN_395 CHAR(4000),
             SYN_396 CHAR(4000),
             SYN_397 CHAR(4000),
             SYN_398 CHAR(4000),
             SYN_399 CHAR(4000),
             SYN_400 CHAR(4000),
             SYN_401 CHAR(4000),
             SYN_402 CHAR(4000),
             SYN_403 CHAR(4000),
             SYN_404 CHAR(4000),
             SYN_405 CHAR(4000),
             SYN_406 CHAR(4000),
             SYN_407 CHAR(4000),
             SYN_408 CHAR(4000),
             SYN_409 CHAR(4000),
             SYN_410 CHAR(4000),
             SYN_411 CHAR(4000),
             SYN_412 CHAR(4000),
             SYN_413 CHAR(4000),
             SYN_414 CHAR(4000),
             SYN_415 CHAR(4000),
             SYN_416 CHAR(4000),
             SYN_417 CHAR(4000),
             SYN_418 CHAR(4000),
             SYN_419 CHAR(4000),
             SYN_420 CHAR(4000),
             SYN_421 CHAR(4000),
             SYN_422 CHAR(4000),
             SYN_423 CHAR(4000),
             SYN_424 CHAR(4000),
             SYN_425 CHAR(4000),
             SYN_426 CHAR(4000),
             SYN_427 CHAR(4000),
             SYN_428 CHAR(4000),
             SYN_429 CHAR(4000)
           )
