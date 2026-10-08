// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'database.dart';

// ignore_for_file: type=lint
class $LocalProductsTable extends LocalProducts
    with TableInfo<$LocalProductsTable, LocalProduct> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $LocalProductsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _serverIdMeta = const VerificationMeta(
    'serverId',
  );
  @override
  late final GeneratedColumn<String> serverId = GeneratedColumn<String>(
    'server_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _nameMeta = const VerificationMeta('name');
  @override
  late final GeneratedColumn<String> name = GeneratedColumn<String>(
    'name',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _searchNameMeta = const VerificationMeta(
    'searchName',
  );
  @override
  late final GeneratedColumn<String> searchName = GeneratedColumn<String>(
    'search_name',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _barcodeMeta = const VerificationMeta(
    'barcode',
  );
  @override
  late final GeneratedColumn<String> barcode = GeneratedColumn<String>(
    'barcode',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _sizeMeta = const VerificationMeta('size');
  @override
  late final GeneratedColumn<String> size = GeneratedColumn<String>(
    'size',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _priceCentsMeta = const VerificationMeta(
    'priceCents',
  );
  @override
  late final GeneratedColumn<int> priceCents = GeneratedColumn<int>(
    'price_cents',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _activeMeta = const VerificationMeta('active');
  @override
  late final GeneratedColumn<bool> active = GeneratedColumn<bool>(
    'active',
    aliasedName,
    false,
    type: DriftSqlType.bool,
    requiredDuringInsert: true,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'CHECK ("active" IN (0, 1))',
    ),
  );
  static const VerificationMeta _revisionMeta = const VerificationMeta(
    'revision',
  );
  @override
  late final GeneratedColumn<String> revision = GeneratedColumn<String>(
    'revision',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [
    serverId,
    name,
    searchName,
    barcode,
    size,
    priceCents,
    active,
    revision,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'local_products';
  @override
  VerificationContext validateIntegrity(
    Insertable<LocalProduct> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('server_id')) {
      context.handle(
        _serverIdMeta,
        serverId.isAcceptableOrUnknown(data['server_id']!, _serverIdMeta),
      );
    } else if (isInserting) {
      context.missing(_serverIdMeta);
    }
    if (data.containsKey('name')) {
      context.handle(
        _nameMeta,
        name.isAcceptableOrUnknown(data['name']!, _nameMeta),
      );
    } else if (isInserting) {
      context.missing(_nameMeta);
    }
    if (data.containsKey('search_name')) {
      context.handle(
        _searchNameMeta,
        searchName.isAcceptableOrUnknown(data['search_name']!, _searchNameMeta),
      );
    } else if (isInserting) {
      context.missing(_searchNameMeta);
    }
    if (data.containsKey('barcode')) {
      context.handle(
        _barcodeMeta,
        barcode.isAcceptableOrUnknown(data['barcode']!, _barcodeMeta),
      );
    } else if (isInserting) {
      context.missing(_barcodeMeta);
    }
    if (data.containsKey('size')) {
      context.handle(
        _sizeMeta,
        size.isAcceptableOrUnknown(data['size']!, _sizeMeta),
      );
    } else if (isInserting) {
      context.missing(_sizeMeta);
    }
    if (data.containsKey('price_cents')) {
      context.handle(
        _priceCentsMeta,
        priceCents.isAcceptableOrUnknown(data['price_cents']!, _priceCentsMeta),
      );
    } else if (isInserting) {
      context.missing(_priceCentsMeta);
    }
    if (data.containsKey('active')) {
      context.handle(
        _activeMeta,
        active.isAcceptableOrUnknown(data['active']!, _activeMeta),
      );
    } else if (isInserting) {
      context.missing(_activeMeta);
    }
    if (data.containsKey('revision')) {
      context.handle(
        _revisionMeta,
        revision.isAcceptableOrUnknown(data['revision']!, _revisionMeta),
      );
    } else if (isInserting) {
      context.missing(_revisionMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {serverId};
  @override
  LocalProduct map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return LocalProduct(
      serverId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}server_id'],
      )!,
      name: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}name'],
      )!,
      searchName: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}search_name'],
      )!,
      barcode: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}barcode'],
      )!,
      size: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}size'],
      )!,
      priceCents: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}price_cents'],
      )!,
      active: attachedDatabase.typeMapping.read(
        DriftSqlType.bool,
        data['${effectivePrefix}active'],
      )!,
      revision: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}revision'],
      )!,
    );
  }

  @override
  $LocalProductsTable createAlias(String alias) {
    return $LocalProductsTable(attachedDatabase, alias);
  }
}

class LocalProduct extends DataClass implements Insertable<LocalProduct> {
  final String serverId;
  final String name;
  final String searchName;
  final String barcode;
  final String size;
  final int priceCents;
  final bool active;
  final String revision;
  const LocalProduct({
    required this.serverId,
    required this.name,
    required this.searchName,
    required this.barcode,
    required this.size,
    required this.priceCents,
    required this.active,
    required this.revision,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['server_id'] = Variable<String>(serverId);
    map['name'] = Variable<String>(name);
    map['search_name'] = Variable<String>(searchName);
    map['barcode'] = Variable<String>(barcode);
    map['size'] = Variable<String>(size);
    map['price_cents'] = Variable<int>(priceCents);
    map['active'] = Variable<bool>(active);
    map['revision'] = Variable<String>(revision);
    return map;
  }

  LocalProductsCompanion toCompanion(bool nullToAbsent) {
    return LocalProductsCompanion(
      serverId: Value(serverId),
      name: Value(name),
      searchName: Value(searchName),
      barcode: Value(barcode),
      size: Value(size),
      priceCents: Value(priceCents),
      active: Value(active),
      revision: Value(revision),
    );
  }

  factory LocalProduct.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return LocalProduct(
      serverId: serializer.fromJson<String>(json['serverId']),
      name: serializer.fromJson<String>(json['name']),
      searchName: serializer.fromJson<String>(json['searchName']),
      barcode: serializer.fromJson<String>(json['barcode']),
      size: serializer.fromJson<String>(json['size']),
      priceCents: serializer.fromJson<int>(json['priceCents']),
      active: serializer.fromJson<bool>(json['active']),
      revision: serializer.fromJson<String>(json['revision']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'serverId': serializer.toJson<String>(serverId),
      'name': serializer.toJson<String>(name),
      'searchName': serializer.toJson<String>(searchName),
      'barcode': serializer.toJson<String>(barcode),
      'size': serializer.toJson<String>(size),
      'priceCents': serializer.toJson<int>(priceCents),
      'active': serializer.toJson<bool>(active),
      'revision': serializer.toJson<String>(revision),
    };
  }

  LocalProduct copyWith({
    String? serverId,
    String? name,
    String? searchName,
    String? barcode,
    String? size,
    int? priceCents,
    bool? active,
    String? revision,
  }) => LocalProduct(
    serverId: serverId ?? this.serverId,
    name: name ?? this.name,
    searchName: searchName ?? this.searchName,
    barcode: barcode ?? this.barcode,
    size: size ?? this.size,
    priceCents: priceCents ?? this.priceCents,
    active: active ?? this.active,
    revision: revision ?? this.revision,
  );
  LocalProduct copyWithCompanion(LocalProductsCompanion data) {
    return LocalProduct(
      serverId: data.serverId.present ? data.serverId.value : this.serverId,
      name: data.name.present ? data.name.value : this.name,
      searchName: data.searchName.present
          ? data.searchName.value
          : this.searchName,
      barcode: data.barcode.present ? data.barcode.value : this.barcode,
      size: data.size.present ? data.size.value : this.size,
      priceCents: data.priceCents.present
          ? data.priceCents.value
          : this.priceCents,
      active: data.active.present ? data.active.value : this.active,
      revision: data.revision.present ? data.revision.value : this.revision,
    );
  }

  @override
  String toString() {
    return (StringBuffer('LocalProduct(')
          ..write('serverId: $serverId, ')
          ..write('name: $name, ')
          ..write('searchName: $searchName, ')
          ..write('barcode: $barcode, ')
          ..write('size: $size, ')
          ..write('priceCents: $priceCents, ')
          ..write('active: $active, ')
          ..write('revision: $revision')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    serverId,
    name,
    searchName,
    barcode,
    size,
    priceCents,
    active,
    revision,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is LocalProduct &&
          other.serverId == this.serverId &&
          other.name == this.name &&
          other.searchName == this.searchName &&
          other.barcode == this.barcode &&
          other.size == this.size &&
          other.priceCents == this.priceCents &&
          other.active == this.active &&
          other.revision == this.revision);
}

class LocalProductsCompanion extends UpdateCompanion<LocalProduct> {
  final Value<String> serverId;
  final Value<String> name;
  final Value<String> searchName;
  final Value<String> barcode;
  final Value<String> size;
  final Value<int> priceCents;
  final Value<bool> active;
  final Value<String> revision;
  final Value<int> rowid;
  const LocalProductsCompanion({
    this.serverId = const Value.absent(),
    this.name = const Value.absent(),
    this.searchName = const Value.absent(),
    this.barcode = const Value.absent(),
    this.size = const Value.absent(),
    this.priceCents = const Value.absent(),
    this.active = const Value.absent(),
    this.revision = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  LocalProductsCompanion.insert({
    required String serverId,
    required String name,
    required String searchName,
    required String barcode,
    required String size,
    required int priceCents,
    required bool active,
    required String revision,
    this.rowid = const Value.absent(),
  }) : serverId = Value(serverId),
       name = Value(name),
       searchName = Value(searchName),
       barcode = Value(barcode),
       size = Value(size),
       priceCents = Value(priceCents),
       active = Value(active),
       revision = Value(revision);
  static Insertable<LocalProduct> custom({
    Expression<String>? serverId,
    Expression<String>? name,
    Expression<String>? searchName,
    Expression<String>? barcode,
    Expression<String>? size,
    Expression<int>? priceCents,
    Expression<bool>? active,
    Expression<String>? revision,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (serverId != null) 'server_id': serverId,
      if (name != null) 'name': name,
      if (searchName != null) 'search_name': searchName,
      if (barcode != null) 'barcode': barcode,
      if (size != null) 'size': size,
      if (priceCents != null) 'price_cents': priceCents,
      if (active != null) 'active': active,
      if (revision != null) 'revision': revision,
      if (rowid != null) 'rowid': rowid,
    });
  }

  LocalProductsCompanion copyWith({
    Value<String>? serverId,
    Value<String>? name,
    Value<String>? searchName,
    Value<String>? barcode,
    Value<String>? size,
    Value<int>? priceCents,
    Value<bool>? active,
    Value<String>? revision,
    Value<int>? rowid,
  }) {
    return LocalProductsCompanion(
      serverId: serverId ?? this.serverId,
      name: name ?? this.name,
      searchName: searchName ?? this.searchName,
      barcode: barcode ?? this.barcode,
      size: size ?? this.size,
      priceCents: priceCents ?? this.priceCents,
      active: active ?? this.active,
      revision: revision ?? this.revision,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (serverId.present) {
      map['server_id'] = Variable<String>(serverId.value);
    }
    if (name.present) {
      map['name'] = Variable<String>(name.value);
    }
    if (searchName.present) {
      map['search_name'] = Variable<String>(searchName.value);
    }
    if (barcode.present) {
      map['barcode'] = Variable<String>(barcode.value);
    }
    if (size.present) {
      map['size'] = Variable<String>(size.value);
    }
    if (priceCents.present) {
      map['price_cents'] = Variable<int>(priceCents.value);
    }
    if (active.present) {
      map['active'] = Variable<bool>(active.value);
    }
    if (revision.present) {
      map['revision'] = Variable<String>(revision.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('LocalProductsCompanion(')
          ..write('serverId: $serverId, ')
          ..write('name: $name, ')
          ..write('searchName: $searchName, ')
          ..write('barcode: $barcode, ')
          ..write('size: $size, ')
          ..write('priceCents: $priceCents, ')
          ..write('active: $active, ')
          ..write('revision: $revision, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $LocalCustomersTable extends LocalCustomers
    with TableInfo<$LocalCustomersTable, LocalCustomer> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $LocalCustomersTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _serverIdMeta = const VerificationMeta(
    'serverId',
  );
  @override
  late final GeneratedColumn<String> serverId = GeneratedColumn<String>(
    'server_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _nameMeta = const VerificationMeta('name');
  @override
  late final GeneratedColumn<String> name = GeneratedColumn<String>(
    'name',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _searchNameMeta = const VerificationMeta(
    'searchName',
  );
  @override
  late final GeneratedColumn<String> searchName = GeneratedColumn<String>(
    'search_name',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _discountBasisPointsMeta =
      const VerificationMeta('discountBasisPoints');
  @override
  late final GeneratedColumn<int> discountBasisPoints = GeneratedColumn<int>(
    'discount_basis_points',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _activeMeta = const VerificationMeta('active');
  @override
  late final GeneratedColumn<bool> active = GeneratedColumn<bool>(
    'active',
    aliasedName,
    false,
    type: DriftSqlType.bool,
    requiredDuringInsert: true,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'CHECK ("active" IN (0, 1))',
    ),
  );
  static const VerificationMeta _revisionMeta = const VerificationMeta(
    'revision',
  );
  @override
  late final GeneratedColumn<String> revision = GeneratedColumn<String>(
    'revision',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [
    serverId,
    name,
    searchName,
    discountBasisPoints,
    active,
    revision,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'local_customers';
  @override
  VerificationContext validateIntegrity(
    Insertable<LocalCustomer> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('server_id')) {
      context.handle(
        _serverIdMeta,
        serverId.isAcceptableOrUnknown(data['server_id']!, _serverIdMeta),
      );
    } else if (isInserting) {
      context.missing(_serverIdMeta);
    }
    if (data.containsKey('name')) {
      context.handle(
        _nameMeta,
        name.isAcceptableOrUnknown(data['name']!, _nameMeta),
      );
    } else if (isInserting) {
      context.missing(_nameMeta);
    }
    if (data.containsKey('search_name')) {
      context.handle(
        _searchNameMeta,
        searchName.isAcceptableOrUnknown(data['search_name']!, _searchNameMeta),
      );
    } else if (isInserting) {
      context.missing(_searchNameMeta);
    }
    if (data.containsKey('discount_basis_points')) {
      context.handle(
        _discountBasisPointsMeta,
        discountBasisPoints.isAcceptableOrUnknown(
          data['discount_basis_points']!,
          _discountBasisPointsMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_discountBasisPointsMeta);
    }
    if (data.containsKey('active')) {
      context.handle(
        _activeMeta,
        active.isAcceptableOrUnknown(data['active']!, _activeMeta),
      );
    } else if (isInserting) {
      context.missing(_activeMeta);
    }
    if (data.containsKey('revision')) {
      context.handle(
        _revisionMeta,
        revision.isAcceptableOrUnknown(data['revision']!, _revisionMeta),
      );
    } else if (isInserting) {
      context.missing(_revisionMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {serverId};
  @override
  LocalCustomer map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return LocalCustomer(
      serverId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}server_id'],
      )!,
      name: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}name'],
      )!,
      searchName: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}search_name'],
      )!,
      discountBasisPoints: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}discount_basis_points'],
      )!,
      active: attachedDatabase.typeMapping.read(
        DriftSqlType.bool,
        data['${effectivePrefix}active'],
      )!,
      revision: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}revision'],
      )!,
    );
  }

  @override
  $LocalCustomersTable createAlias(String alias) {
    return $LocalCustomersTable(attachedDatabase, alias);
  }
}

class LocalCustomer extends DataClass implements Insertable<LocalCustomer> {
  final String serverId;
  final String name;
  final String searchName;
  final int discountBasisPoints;
  final bool active;
  final String revision;
  const LocalCustomer({
    required this.serverId,
    required this.name,
    required this.searchName,
    required this.discountBasisPoints,
    required this.active,
    required this.revision,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['server_id'] = Variable<String>(serverId);
    map['name'] = Variable<String>(name);
    map['search_name'] = Variable<String>(searchName);
    map['discount_basis_points'] = Variable<int>(discountBasisPoints);
    map['active'] = Variable<bool>(active);
    map['revision'] = Variable<String>(revision);
    return map;
  }

  LocalCustomersCompanion toCompanion(bool nullToAbsent) {
    return LocalCustomersCompanion(
      serverId: Value(serverId),
      name: Value(name),
      searchName: Value(searchName),
      discountBasisPoints: Value(discountBasisPoints),
      active: Value(active),
      revision: Value(revision),
    );
  }

  factory LocalCustomer.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return LocalCustomer(
      serverId: serializer.fromJson<String>(json['serverId']),
      name: serializer.fromJson<String>(json['name']),
      searchName: serializer.fromJson<String>(json['searchName']),
      discountBasisPoints: serializer.fromJson<int>(
        json['discountBasisPoints'],
      ),
      active: serializer.fromJson<bool>(json['active']),
      revision: serializer.fromJson<String>(json['revision']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'serverId': serializer.toJson<String>(serverId),
      'name': serializer.toJson<String>(name),
      'searchName': serializer.toJson<String>(searchName),
      'discountBasisPoints': serializer.toJson<int>(discountBasisPoints),
      'active': serializer.toJson<bool>(active),
      'revision': serializer.toJson<String>(revision),
    };
  }

  LocalCustomer copyWith({
    String? serverId,
    String? name,
    String? searchName,
    int? discountBasisPoints,
    bool? active,
    String? revision,
  }) => LocalCustomer(
    serverId: serverId ?? this.serverId,
    name: name ?? this.name,
    searchName: searchName ?? this.searchName,
    discountBasisPoints: discountBasisPoints ?? this.discountBasisPoints,
    active: active ?? this.active,
    revision: revision ?? this.revision,
  );
  LocalCustomer copyWithCompanion(LocalCustomersCompanion data) {
    return LocalCustomer(
      serverId: data.serverId.present ? data.serverId.value : this.serverId,
      name: data.name.present ? data.name.value : this.name,
      searchName: data.searchName.present
          ? data.searchName.value
          : this.searchName,
      discountBasisPoints: data.discountBasisPoints.present
          ? data.discountBasisPoints.value
          : this.discountBasisPoints,
      active: data.active.present ? data.active.value : this.active,
      revision: data.revision.present ? data.revision.value : this.revision,
    );
  }

  @override
  String toString() {
    return (StringBuffer('LocalCustomer(')
          ..write('serverId: $serverId, ')
          ..write('name: $name, ')
          ..write('searchName: $searchName, ')
          ..write('discountBasisPoints: $discountBasisPoints, ')
          ..write('active: $active, ')
          ..write('revision: $revision')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    serverId,
    name,
    searchName,
    discountBasisPoints,
    active,
    revision,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is LocalCustomer &&
          other.serverId == this.serverId &&
          other.name == this.name &&
          other.searchName == this.searchName &&
          other.discountBasisPoints == this.discountBasisPoints &&
          other.active == this.active &&
          other.revision == this.revision);
}

class LocalCustomersCompanion extends UpdateCompanion<LocalCustomer> {
  final Value<String> serverId;
  final Value<String> name;
  final Value<String> searchName;
  final Value<int> discountBasisPoints;
  final Value<bool> active;
  final Value<String> revision;
  final Value<int> rowid;
  const LocalCustomersCompanion({
    this.serverId = const Value.absent(),
    this.name = const Value.absent(),
    this.searchName = const Value.absent(),
    this.discountBasisPoints = const Value.absent(),
    this.active = const Value.absent(),
    this.revision = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  LocalCustomersCompanion.insert({
    required String serverId,
    required String name,
    required String searchName,
    required int discountBasisPoints,
    required bool active,
    required String revision,
    this.rowid = const Value.absent(),
  }) : serverId = Value(serverId),
       name = Value(name),
       searchName = Value(searchName),
       discountBasisPoints = Value(discountBasisPoints),
       active = Value(active),
       revision = Value(revision);
  static Insertable<LocalCustomer> custom({
    Expression<String>? serverId,
    Expression<String>? name,
    Expression<String>? searchName,
    Expression<int>? discountBasisPoints,
    Expression<bool>? active,
    Expression<String>? revision,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (serverId != null) 'server_id': serverId,
      if (name != null) 'name': name,
      if (searchName != null) 'search_name': searchName,
      if (discountBasisPoints != null)
        'discount_basis_points': discountBasisPoints,
      if (active != null) 'active': active,
      if (revision != null) 'revision': revision,
      if (rowid != null) 'rowid': rowid,
    });
  }

  LocalCustomersCompanion copyWith({
    Value<String>? serverId,
    Value<String>? name,
    Value<String>? searchName,
    Value<int>? discountBasisPoints,
    Value<bool>? active,
    Value<String>? revision,
    Value<int>? rowid,
  }) {
    return LocalCustomersCompanion(
      serverId: serverId ?? this.serverId,
      name: name ?? this.name,
      searchName: searchName ?? this.searchName,
      discountBasisPoints: discountBasisPoints ?? this.discountBasisPoints,
      active: active ?? this.active,
      revision: revision ?? this.revision,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (serverId.present) {
      map['server_id'] = Variable<String>(serverId.value);
    }
    if (name.present) {
      map['name'] = Variable<String>(name.value);
    }
    if (searchName.present) {
      map['search_name'] = Variable<String>(searchName.value);
    }
    if (discountBasisPoints.present) {
      map['discount_basis_points'] = Variable<int>(discountBasisPoints.value);
    }
    if (active.present) {
      map['active'] = Variable<bool>(active.value);
    }
    if (revision.present) {
      map['revision'] = Variable<String>(revision.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('LocalCustomersCompanion(')
          ..write('serverId: $serverId, ')
          ..write('name: $name, ')
          ..write('searchName: $searchName, ')
          ..write('discountBasisPoints: $discountBasisPoints, ')
          ..write('active: $active, ')
          ..write('revision: $revision, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $StockSnapshotsTable extends StockSnapshots
    with TableInfo<$StockSnapshotsTable, StockSnapshot> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $StockSnapshotsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _productIdMeta = const VerificationMeta(
    'productId',
  );
  @override
  late final GeneratedColumn<String> productId = GeneratedColumn<String>(
    'product_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _quantityUnitsMeta = const VerificationMeta(
    'quantityUnits',
  );
  @override
  late final GeneratedColumn<int> quantityUnits = GeneratedColumn<int>(
    'quantity_units',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _revisionMeta = const VerificationMeta(
    'revision',
  );
  @override
  late final GeneratedColumn<String> revision = GeneratedColumn<String>(
    'revision',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [productId, quantityUnits, revision];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'stock_snapshots';
  @override
  VerificationContext validateIntegrity(
    Insertable<StockSnapshot> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('product_id')) {
      context.handle(
        _productIdMeta,
        productId.isAcceptableOrUnknown(data['product_id']!, _productIdMeta),
      );
    } else if (isInserting) {
      context.missing(_productIdMeta);
    }
    if (data.containsKey('quantity_units')) {
      context.handle(
        _quantityUnitsMeta,
        quantityUnits.isAcceptableOrUnknown(
          data['quantity_units']!,
          _quantityUnitsMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_quantityUnitsMeta);
    }
    if (data.containsKey('revision')) {
      context.handle(
        _revisionMeta,
        revision.isAcceptableOrUnknown(data['revision']!, _revisionMeta),
      );
    } else if (isInserting) {
      context.missing(_revisionMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {productId};
  @override
  StockSnapshot map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return StockSnapshot(
      productId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}product_id'],
      )!,
      quantityUnits: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}quantity_units'],
      )!,
      revision: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}revision'],
      )!,
    );
  }

  @override
  $StockSnapshotsTable createAlias(String alias) {
    return $StockSnapshotsTable(attachedDatabase, alias);
  }
}

class StockSnapshot extends DataClass implements Insertable<StockSnapshot> {
  final String productId;
  final int quantityUnits;
  final String revision;
  const StockSnapshot({
    required this.productId,
    required this.quantityUnits,
    required this.revision,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['product_id'] = Variable<String>(productId);
    map['quantity_units'] = Variable<int>(quantityUnits);
    map['revision'] = Variable<String>(revision);
    return map;
  }

  StockSnapshotsCompanion toCompanion(bool nullToAbsent) {
    return StockSnapshotsCompanion(
      productId: Value(productId),
      quantityUnits: Value(quantityUnits),
      revision: Value(revision),
    );
  }

  factory StockSnapshot.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return StockSnapshot(
      productId: serializer.fromJson<String>(json['productId']),
      quantityUnits: serializer.fromJson<int>(json['quantityUnits']),
      revision: serializer.fromJson<String>(json['revision']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'productId': serializer.toJson<String>(productId),
      'quantityUnits': serializer.toJson<int>(quantityUnits),
      'revision': serializer.toJson<String>(revision),
    };
  }

  StockSnapshot copyWith({
    String? productId,
    int? quantityUnits,
    String? revision,
  }) => StockSnapshot(
    productId: productId ?? this.productId,
    quantityUnits: quantityUnits ?? this.quantityUnits,
    revision: revision ?? this.revision,
  );
  StockSnapshot copyWithCompanion(StockSnapshotsCompanion data) {
    return StockSnapshot(
      productId: data.productId.present ? data.productId.value : this.productId,
      quantityUnits: data.quantityUnits.present
          ? data.quantityUnits.value
          : this.quantityUnits,
      revision: data.revision.present ? data.revision.value : this.revision,
    );
  }

  @override
  String toString() {
    return (StringBuffer('StockSnapshot(')
          ..write('productId: $productId, ')
          ..write('quantityUnits: $quantityUnits, ')
          ..write('revision: $revision')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(productId, quantityUnits, revision);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is StockSnapshot &&
          other.productId == this.productId &&
          other.quantityUnits == this.quantityUnits &&
          other.revision == this.revision);
}

class StockSnapshotsCompanion extends UpdateCompanion<StockSnapshot> {
  final Value<String> productId;
  final Value<int> quantityUnits;
  final Value<String> revision;
  final Value<int> rowid;
  const StockSnapshotsCompanion({
    this.productId = const Value.absent(),
    this.quantityUnits = const Value.absent(),
    this.revision = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  StockSnapshotsCompanion.insert({
    required String productId,
    required int quantityUnits,
    required String revision,
    this.rowid = const Value.absent(),
  }) : productId = Value(productId),
       quantityUnits = Value(quantityUnits),
       revision = Value(revision);
  static Insertable<StockSnapshot> custom({
    Expression<String>? productId,
    Expression<int>? quantityUnits,
    Expression<String>? revision,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (productId != null) 'product_id': productId,
      if (quantityUnits != null) 'quantity_units': quantityUnits,
      if (revision != null) 'revision': revision,
      if (rowid != null) 'rowid': rowid,
    });
  }

  StockSnapshotsCompanion copyWith({
    Value<String>? productId,
    Value<int>? quantityUnits,
    Value<String>? revision,
    Value<int>? rowid,
  }) {
    return StockSnapshotsCompanion(
      productId: productId ?? this.productId,
      quantityUnits: quantityUnits ?? this.quantityUnits,
      revision: revision ?? this.revision,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (productId.present) {
      map['product_id'] = Variable<String>(productId.value);
    }
    if (quantityUnits.present) {
      map['quantity_units'] = Variable<int>(quantityUnits.value);
    }
    if (revision.present) {
      map['revision'] = Variable<String>(revision.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('StockSnapshotsCompanion(')
          ..write('productId: $productId, ')
          ..write('quantityUnits: $quantityUnits, ')
          ..write('revision: $revision, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $AccountSnapshotsTable extends AccountSnapshots
    with TableInfo<$AccountSnapshotsTable, AccountSnapshot> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $AccountSnapshotsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _customerIdMeta = const VerificationMeta(
    'customerId',
  );
  @override
  late final GeneratedColumn<String> customerId = GeneratedColumn<String>(
    'customer_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _debtCentsMeta = const VerificationMeta(
    'debtCents',
  );
  @override
  late final GeneratedColumn<int> debtCents = GeneratedColumn<int>(
    'debt_cents',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _paymentCentsMeta = const VerificationMeta(
    'paymentCents',
  );
  @override
  late final GeneratedColumn<int> paymentCents = GeneratedColumn<int>(
    'payment_cents',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _balanceCentsMeta = const VerificationMeta(
    'balanceCents',
  );
  @override
  late final GeneratedColumn<int> balanceCents = GeneratedColumn<int>(
    'balance_cents',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _revisionMeta = const VerificationMeta(
    'revision',
  );
  @override
  late final GeneratedColumn<String> revision = GeneratedColumn<String>(
    'revision',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [
    customerId,
    debtCents,
    paymentCents,
    balanceCents,
    revision,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'account_snapshots';
  @override
  VerificationContext validateIntegrity(
    Insertable<AccountSnapshot> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('customer_id')) {
      context.handle(
        _customerIdMeta,
        customerId.isAcceptableOrUnknown(data['customer_id']!, _customerIdMeta),
      );
    } else if (isInserting) {
      context.missing(_customerIdMeta);
    }
    if (data.containsKey('debt_cents')) {
      context.handle(
        _debtCentsMeta,
        debtCents.isAcceptableOrUnknown(data['debt_cents']!, _debtCentsMeta),
      );
    } else if (isInserting) {
      context.missing(_debtCentsMeta);
    }
    if (data.containsKey('payment_cents')) {
      context.handle(
        _paymentCentsMeta,
        paymentCents.isAcceptableOrUnknown(
          data['payment_cents']!,
          _paymentCentsMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_paymentCentsMeta);
    }
    if (data.containsKey('balance_cents')) {
      context.handle(
        _balanceCentsMeta,
        balanceCents.isAcceptableOrUnknown(
          data['balance_cents']!,
          _balanceCentsMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_balanceCentsMeta);
    }
    if (data.containsKey('revision')) {
      context.handle(
        _revisionMeta,
        revision.isAcceptableOrUnknown(data['revision']!, _revisionMeta),
      );
    } else if (isInserting) {
      context.missing(_revisionMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {customerId};
  @override
  AccountSnapshot map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return AccountSnapshot(
      customerId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}customer_id'],
      )!,
      debtCents: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}debt_cents'],
      )!,
      paymentCents: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}payment_cents'],
      )!,
      balanceCents: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}balance_cents'],
      )!,
      revision: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}revision'],
      )!,
    );
  }

  @override
  $AccountSnapshotsTable createAlias(String alias) {
    return $AccountSnapshotsTable(attachedDatabase, alias);
  }
}

class AccountSnapshot extends DataClass implements Insertable<AccountSnapshot> {
  final String customerId;
  final int debtCents;
  final int paymentCents;
  final int balanceCents;
  final String revision;
  const AccountSnapshot({
    required this.customerId,
    required this.debtCents,
    required this.paymentCents,
    required this.balanceCents,
    required this.revision,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['customer_id'] = Variable<String>(customerId);
    map['debt_cents'] = Variable<int>(debtCents);
    map['payment_cents'] = Variable<int>(paymentCents);
    map['balance_cents'] = Variable<int>(balanceCents);
    map['revision'] = Variable<String>(revision);
    return map;
  }

  AccountSnapshotsCompanion toCompanion(bool nullToAbsent) {
    return AccountSnapshotsCompanion(
      customerId: Value(customerId),
      debtCents: Value(debtCents),
      paymentCents: Value(paymentCents),
      balanceCents: Value(balanceCents),
      revision: Value(revision),
    );
  }

  factory AccountSnapshot.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return AccountSnapshot(
      customerId: serializer.fromJson<String>(json['customerId']),
      debtCents: serializer.fromJson<int>(json['debtCents']),
      paymentCents: serializer.fromJson<int>(json['paymentCents']),
      balanceCents: serializer.fromJson<int>(json['balanceCents']),
      revision: serializer.fromJson<String>(json['revision']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'customerId': serializer.toJson<String>(customerId),
      'debtCents': serializer.toJson<int>(debtCents),
      'paymentCents': serializer.toJson<int>(paymentCents),
      'balanceCents': serializer.toJson<int>(balanceCents),
      'revision': serializer.toJson<String>(revision),
    };
  }

  AccountSnapshot copyWith({
    String? customerId,
    int? debtCents,
    int? paymentCents,
    int? balanceCents,
    String? revision,
  }) => AccountSnapshot(
    customerId: customerId ?? this.customerId,
    debtCents: debtCents ?? this.debtCents,
    paymentCents: paymentCents ?? this.paymentCents,
    balanceCents: balanceCents ?? this.balanceCents,
    revision: revision ?? this.revision,
  );
  AccountSnapshot copyWithCompanion(AccountSnapshotsCompanion data) {
    return AccountSnapshot(
      customerId: data.customerId.present
          ? data.customerId.value
          : this.customerId,
      debtCents: data.debtCents.present ? data.debtCents.value : this.debtCents,
      paymentCents: data.paymentCents.present
          ? data.paymentCents.value
          : this.paymentCents,
      balanceCents: data.balanceCents.present
          ? data.balanceCents.value
          : this.balanceCents,
      revision: data.revision.present ? data.revision.value : this.revision,
    );
  }

  @override
  String toString() {
    return (StringBuffer('AccountSnapshot(')
          ..write('customerId: $customerId, ')
          ..write('debtCents: $debtCents, ')
          ..write('paymentCents: $paymentCents, ')
          ..write('balanceCents: $balanceCents, ')
          ..write('revision: $revision')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode =>
      Object.hash(customerId, debtCents, paymentCents, balanceCents, revision);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is AccountSnapshot &&
          other.customerId == this.customerId &&
          other.debtCents == this.debtCents &&
          other.paymentCents == this.paymentCents &&
          other.balanceCents == this.balanceCents &&
          other.revision == this.revision);
}

class AccountSnapshotsCompanion extends UpdateCompanion<AccountSnapshot> {
  final Value<String> customerId;
  final Value<int> debtCents;
  final Value<int> paymentCents;
  final Value<int> balanceCents;
  final Value<String> revision;
  final Value<int> rowid;
  const AccountSnapshotsCompanion({
    this.customerId = const Value.absent(),
    this.debtCents = const Value.absent(),
    this.paymentCents = const Value.absent(),
    this.balanceCents = const Value.absent(),
    this.revision = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  AccountSnapshotsCompanion.insert({
    required String customerId,
    required int debtCents,
    required int paymentCents,
    required int balanceCents,
    required String revision,
    this.rowid = const Value.absent(),
  }) : customerId = Value(customerId),
       debtCents = Value(debtCents),
       paymentCents = Value(paymentCents),
       balanceCents = Value(balanceCents),
       revision = Value(revision);
  static Insertable<AccountSnapshot> custom({
    Expression<String>? customerId,
    Expression<int>? debtCents,
    Expression<int>? paymentCents,
    Expression<int>? balanceCents,
    Expression<String>? revision,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (customerId != null) 'customer_id': customerId,
      if (debtCents != null) 'debt_cents': debtCents,
      if (paymentCents != null) 'payment_cents': paymentCents,
      if (balanceCents != null) 'balance_cents': balanceCents,
      if (revision != null) 'revision': revision,
      if (rowid != null) 'rowid': rowid,
    });
  }

  AccountSnapshotsCompanion copyWith({
    Value<String>? customerId,
    Value<int>? debtCents,
    Value<int>? paymentCents,
    Value<int>? balanceCents,
    Value<String>? revision,
    Value<int>? rowid,
  }) {
    return AccountSnapshotsCompanion(
      customerId: customerId ?? this.customerId,
      debtCents: debtCents ?? this.debtCents,
      paymentCents: paymentCents ?? this.paymentCents,
      balanceCents: balanceCents ?? this.balanceCents,
      revision: revision ?? this.revision,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (customerId.present) {
      map['customer_id'] = Variable<String>(customerId.value);
    }
    if (debtCents.present) {
      map['debt_cents'] = Variable<int>(debtCents.value);
    }
    if (paymentCents.present) {
      map['payment_cents'] = Variable<int>(paymentCents.value);
    }
    if (balanceCents.present) {
      map['balance_cents'] = Variable<int>(balanceCents.value);
    }
    if (revision.present) {
      map['revision'] = Variable<String>(revision.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('AccountSnapshotsCompanion(')
          ..write('customerId: $customerId, ')
          ..write('debtCents: $debtCents, ')
          ..write('paymentCents: $paymentCents, ')
          ..write('balanceCents: $balanceCents, ')
          ..write('revision: $revision, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $SyncMetadataTable extends SyncMetadata
    with TableInfo<$SyncMetadataTable, SyncMetadataData> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $SyncMetadataTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<int> id = GeneratedColumn<int>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _contextIdMeta = const VerificationMeta(
    'contextId',
  );
  @override
  late final GeneratedColumn<String> contextId = GeneratedColumn<String>(
    'context_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _cursorMeta = const VerificationMeta('cursor');
  @override
  late final GeneratedColumn<String> cursor = GeneratedColumn<String>(
    'cursor',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _revisionMeta = const VerificationMeta(
    'revision',
  );
  @override
  late final GeneratedColumn<String> revision = GeneratedColumn<String>(
    'revision',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _syncedAtMeta = const VerificationMeta(
    'syncedAt',
  );
  @override
  late final GeneratedColumn<String> syncedAt = GeneratedColumn<String>(
    'synced_at',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _lastObservedAtMeta = const VerificationMeta(
    'lastObservedAt',
  );
  @override
  late final GeneratedColumn<String> lastObservedAt = GeneratedColumn<String>(
    'last_observed_at',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _defaultCustomerIdMeta = const VerificationMeta(
    'defaultCustomerId',
  );
  @override
  late final GeneratedColumn<String> defaultCustomerId =
      GeneratedColumn<String>(
        'default_customer_id',
        aliasedName,
        true,
        type: DriftSqlType.string,
        requiredDuringInsert: false,
      );
  static const VerificationMeta _authLockedMeta = const VerificationMeta(
    'authLocked',
  );
  @override
  late final GeneratedColumn<bool> authLocked = GeneratedColumn<bool>(
    'auth_locked',
    aliasedName,
    false,
    type: DriftSqlType.bool,
    requiredDuringInsert: false,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'CHECK ("auth_locked" IN (0, 1))',
    ),
    defaultValue: const Constant(false),
  );
  @override
  List<GeneratedColumn> get $columns => [
    id,
    contextId,
    cursor,
    revision,
    syncedAt,
    lastObservedAt,
    defaultCustomerId,
    authLocked,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'sync_metadata';
  @override
  VerificationContext validateIntegrity(
    Insertable<SyncMetadataData> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    }
    if (data.containsKey('context_id')) {
      context.handle(
        _contextIdMeta,
        contextId.isAcceptableOrUnknown(data['context_id']!, _contextIdMeta),
      );
    } else if (isInserting) {
      context.missing(_contextIdMeta);
    }
    if (data.containsKey('cursor')) {
      context.handle(
        _cursorMeta,
        cursor.isAcceptableOrUnknown(data['cursor']!, _cursorMeta),
      );
    }
    if (data.containsKey('revision')) {
      context.handle(
        _revisionMeta,
        revision.isAcceptableOrUnknown(data['revision']!, _revisionMeta),
      );
    }
    if (data.containsKey('synced_at')) {
      context.handle(
        _syncedAtMeta,
        syncedAt.isAcceptableOrUnknown(data['synced_at']!, _syncedAtMeta),
      );
    }
    if (data.containsKey('last_observed_at')) {
      context.handle(
        _lastObservedAtMeta,
        lastObservedAt.isAcceptableOrUnknown(
          data['last_observed_at']!,
          _lastObservedAtMeta,
        ),
      );
    }
    if (data.containsKey('default_customer_id')) {
      context.handle(
        _defaultCustomerIdMeta,
        defaultCustomerId.isAcceptableOrUnknown(
          data['default_customer_id']!,
          _defaultCustomerIdMeta,
        ),
      );
    }
    if (data.containsKey('auth_locked')) {
      context.handle(
        _authLockedMeta,
        authLocked.isAcceptableOrUnknown(data['auth_locked']!, _authLockedMeta),
      );
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  SyncMetadataData map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return SyncMetadataData(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}id'],
      )!,
      contextId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}context_id'],
      )!,
      cursor: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}cursor'],
      ),
      revision: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}revision'],
      ),
      syncedAt: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}synced_at'],
      ),
      lastObservedAt: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}last_observed_at'],
      ),
      defaultCustomerId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}default_customer_id'],
      ),
      authLocked: attachedDatabase.typeMapping.read(
        DriftSqlType.bool,
        data['${effectivePrefix}auth_locked'],
      )!,
    );
  }

  @override
  $SyncMetadataTable createAlias(String alias) {
    return $SyncMetadataTable(attachedDatabase, alias);
  }
}

class SyncMetadataData extends DataClass
    implements Insertable<SyncMetadataData> {
  final int id;
  final String contextId;
  final String? cursor;
  final String? revision;
  final String? syncedAt;
  final String? lastObservedAt;
  final String? defaultCustomerId;
  final bool authLocked;
  const SyncMetadataData({
    required this.id,
    required this.contextId,
    this.cursor,
    this.revision,
    this.syncedAt,
    this.lastObservedAt,
    this.defaultCustomerId,
    required this.authLocked,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<int>(id);
    map['context_id'] = Variable<String>(contextId);
    if (!nullToAbsent || cursor != null) {
      map['cursor'] = Variable<String>(cursor);
    }
    if (!nullToAbsent || revision != null) {
      map['revision'] = Variable<String>(revision);
    }
    if (!nullToAbsent || syncedAt != null) {
      map['synced_at'] = Variable<String>(syncedAt);
    }
    if (!nullToAbsent || lastObservedAt != null) {
      map['last_observed_at'] = Variable<String>(lastObservedAt);
    }
    if (!nullToAbsent || defaultCustomerId != null) {
      map['default_customer_id'] = Variable<String>(defaultCustomerId);
    }
    map['auth_locked'] = Variable<bool>(authLocked);
    return map;
  }

  SyncMetadataCompanion toCompanion(bool nullToAbsent) {
    return SyncMetadataCompanion(
      id: Value(id),
      contextId: Value(contextId),
      cursor: cursor == null && nullToAbsent
          ? const Value.absent()
          : Value(cursor),
      revision: revision == null && nullToAbsent
          ? const Value.absent()
          : Value(revision),
      syncedAt: syncedAt == null && nullToAbsent
          ? const Value.absent()
          : Value(syncedAt),
      lastObservedAt: lastObservedAt == null && nullToAbsent
          ? const Value.absent()
          : Value(lastObservedAt),
      defaultCustomerId: defaultCustomerId == null && nullToAbsent
          ? const Value.absent()
          : Value(defaultCustomerId),
      authLocked: Value(authLocked),
    );
  }

  factory SyncMetadataData.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return SyncMetadataData(
      id: serializer.fromJson<int>(json['id']),
      contextId: serializer.fromJson<String>(json['contextId']),
      cursor: serializer.fromJson<String?>(json['cursor']),
      revision: serializer.fromJson<String?>(json['revision']),
      syncedAt: serializer.fromJson<String?>(json['syncedAt']),
      lastObservedAt: serializer.fromJson<String?>(json['lastObservedAt']),
      defaultCustomerId: serializer.fromJson<String?>(
        json['defaultCustomerId'],
      ),
      authLocked: serializer.fromJson<bool>(json['authLocked']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<int>(id),
      'contextId': serializer.toJson<String>(contextId),
      'cursor': serializer.toJson<String?>(cursor),
      'revision': serializer.toJson<String?>(revision),
      'syncedAt': serializer.toJson<String?>(syncedAt),
      'lastObservedAt': serializer.toJson<String?>(lastObservedAt),
      'defaultCustomerId': serializer.toJson<String?>(defaultCustomerId),
      'authLocked': serializer.toJson<bool>(authLocked),
    };
  }

  SyncMetadataData copyWith({
    int? id,
    String? contextId,
    Value<String?> cursor = const Value.absent(),
    Value<String?> revision = const Value.absent(),
    Value<String?> syncedAt = const Value.absent(),
    Value<String?> lastObservedAt = const Value.absent(),
    Value<String?> defaultCustomerId = const Value.absent(),
    bool? authLocked,
  }) => SyncMetadataData(
    id: id ?? this.id,
    contextId: contextId ?? this.contextId,
    cursor: cursor.present ? cursor.value : this.cursor,
    revision: revision.present ? revision.value : this.revision,
    syncedAt: syncedAt.present ? syncedAt.value : this.syncedAt,
    lastObservedAt: lastObservedAt.present
        ? lastObservedAt.value
        : this.lastObservedAt,
    defaultCustomerId: defaultCustomerId.present
        ? defaultCustomerId.value
        : this.defaultCustomerId,
    authLocked: authLocked ?? this.authLocked,
  );
  SyncMetadataData copyWithCompanion(SyncMetadataCompanion data) {
    return SyncMetadataData(
      id: data.id.present ? data.id.value : this.id,
      contextId: data.contextId.present ? data.contextId.value : this.contextId,
      cursor: data.cursor.present ? data.cursor.value : this.cursor,
      revision: data.revision.present ? data.revision.value : this.revision,
      syncedAt: data.syncedAt.present ? data.syncedAt.value : this.syncedAt,
      lastObservedAt: data.lastObservedAt.present
          ? data.lastObservedAt.value
          : this.lastObservedAt,
      defaultCustomerId: data.defaultCustomerId.present
          ? data.defaultCustomerId.value
          : this.defaultCustomerId,
      authLocked: data.authLocked.present
          ? data.authLocked.value
          : this.authLocked,
    );
  }

  @override
  String toString() {
    return (StringBuffer('SyncMetadataData(')
          ..write('id: $id, ')
          ..write('contextId: $contextId, ')
          ..write('cursor: $cursor, ')
          ..write('revision: $revision, ')
          ..write('syncedAt: $syncedAt, ')
          ..write('lastObservedAt: $lastObservedAt, ')
          ..write('defaultCustomerId: $defaultCustomerId, ')
          ..write('authLocked: $authLocked')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    id,
    contextId,
    cursor,
    revision,
    syncedAt,
    lastObservedAt,
    defaultCustomerId,
    authLocked,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is SyncMetadataData &&
          other.id == this.id &&
          other.contextId == this.contextId &&
          other.cursor == this.cursor &&
          other.revision == this.revision &&
          other.syncedAt == this.syncedAt &&
          other.lastObservedAt == this.lastObservedAt &&
          other.defaultCustomerId == this.defaultCustomerId &&
          other.authLocked == this.authLocked);
}

class SyncMetadataCompanion extends UpdateCompanion<SyncMetadataData> {
  final Value<int> id;
  final Value<String> contextId;
  final Value<String?> cursor;
  final Value<String?> revision;
  final Value<String?> syncedAt;
  final Value<String?> lastObservedAt;
  final Value<String?> defaultCustomerId;
  final Value<bool> authLocked;
  const SyncMetadataCompanion({
    this.id = const Value.absent(),
    this.contextId = const Value.absent(),
    this.cursor = const Value.absent(),
    this.revision = const Value.absent(),
    this.syncedAt = const Value.absent(),
    this.lastObservedAt = const Value.absent(),
    this.defaultCustomerId = const Value.absent(),
    this.authLocked = const Value.absent(),
  });
  SyncMetadataCompanion.insert({
    this.id = const Value.absent(),
    required String contextId,
    this.cursor = const Value.absent(),
    this.revision = const Value.absent(),
    this.syncedAt = const Value.absent(),
    this.lastObservedAt = const Value.absent(),
    this.defaultCustomerId = const Value.absent(),
    this.authLocked = const Value.absent(),
  }) : contextId = Value(contextId);
  static Insertable<SyncMetadataData> custom({
    Expression<int>? id,
    Expression<String>? contextId,
    Expression<String>? cursor,
    Expression<String>? revision,
    Expression<String>? syncedAt,
    Expression<String>? lastObservedAt,
    Expression<String>? defaultCustomerId,
    Expression<bool>? authLocked,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (contextId != null) 'context_id': contextId,
      if (cursor != null) 'cursor': cursor,
      if (revision != null) 'revision': revision,
      if (syncedAt != null) 'synced_at': syncedAt,
      if (lastObservedAt != null) 'last_observed_at': lastObservedAt,
      if (defaultCustomerId != null) 'default_customer_id': defaultCustomerId,
      if (authLocked != null) 'auth_locked': authLocked,
    });
  }

  SyncMetadataCompanion copyWith({
    Value<int>? id,
    Value<String>? contextId,
    Value<String?>? cursor,
    Value<String?>? revision,
    Value<String?>? syncedAt,
    Value<String?>? lastObservedAt,
    Value<String?>? defaultCustomerId,
    Value<bool>? authLocked,
  }) {
    return SyncMetadataCompanion(
      id: id ?? this.id,
      contextId: contextId ?? this.contextId,
      cursor: cursor ?? this.cursor,
      revision: revision ?? this.revision,
      syncedAt: syncedAt ?? this.syncedAt,
      lastObservedAt: lastObservedAt ?? this.lastObservedAt,
      defaultCustomerId: defaultCustomerId ?? this.defaultCustomerId,
      authLocked: authLocked ?? this.authLocked,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<int>(id.value);
    }
    if (contextId.present) {
      map['context_id'] = Variable<String>(contextId.value);
    }
    if (cursor.present) {
      map['cursor'] = Variable<String>(cursor.value);
    }
    if (revision.present) {
      map['revision'] = Variable<String>(revision.value);
    }
    if (syncedAt.present) {
      map['synced_at'] = Variable<String>(syncedAt.value);
    }
    if (lastObservedAt.present) {
      map['last_observed_at'] = Variable<String>(lastObservedAt.value);
    }
    if (defaultCustomerId.present) {
      map['default_customer_id'] = Variable<String>(defaultCustomerId.value);
    }
    if (authLocked.present) {
      map['auth_locked'] = Variable<bool>(authLocked.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('SyncMetadataCompanion(')
          ..write('id: $id, ')
          ..write('contextId: $contextId, ')
          ..write('cursor: $cursor, ')
          ..write('revision: $revision, ')
          ..write('syncedAt: $syncedAt, ')
          ..write('lastObservedAt: $lastObservedAt, ')
          ..write('defaultCustomerId: $defaultCustomerId, ')
          ..write('authLocked: $authLocked')
          ..write(')'))
        .toString();
  }
}

class $DownloadsTable extends Downloads
    with TableInfo<$DownloadsTable, Download> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $DownloadsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<int> id = GeneratedColumn<int>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _incrementalMeta = const VerificationMeta(
    'incremental',
  );
  @override
  late final GeneratedColumn<bool> incremental = GeneratedColumn<bool>(
    'incremental',
    aliasedName,
    false,
    type: DriftSqlType.bool,
    requiredDuringInsert: true,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'CHECK ("incremental" IN (0, 1))',
    ),
  );
  static const VerificationMeta _baseCursorMeta = const VerificationMeta(
    'baseCursor',
  );
  @override
  late final GeneratedColumn<String> baseCursor = GeneratedColumn<String>(
    'base_cursor',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _revisionMeta = const VerificationMeta(
    'revision',
  );
  @override
  late final GeneratedColumn<String> revision = GeneratedColumn<String>(
    'revision',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _nextPageTokenMeta = const VerificationMeta(
    'nextPageToken',
  );
  @override
  late final GeneratedColumn<String> nextPageToken = GeneratedColumn<String>(
    'next_page_token',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _pageCountMeta = const VerificationMeta(
    'pageCount',
  );
  @override
  late final GeneratedColumn<int> pageCount = GeneratedColumn<int>(
    'page_count',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
    defaultValue: const Constant(0),
  );
  static const VerificationMeta _completeMeta = const VerificationMeta(
    'complete',
  );
  @override
  late final GeneratedColumn<bool> complete = GeneratedColumn<bool>(
    'complete',
    aliasedName,
    false,
    type: DriftSqlType.bool,
    requiredDuringInsert: false,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'CHECK ("complete" IN (0, 1))',
    ),
    defaultValue: const Constant(false),
  );
  @override
  List<GeneratedColumn> get $columns => [
    id,
    incremental,
    baseCursor,
    revision,
    nextPageToken,
    pageCount,
    complete,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'downloads';
  @override
  VerificationContext validateIntegrity(
    Insertable<Download> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    }
    if (data.containsKey('incremental')) {
      context.handle(
        _incrementalMeta,
        incremental.isAcceptableOrUnknown(
          data['incremental']!,
          _incrementalMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_incrementalMeta);
    }
    if (data.containsKey('base_cursor')) {
      context.handle(
        _baseCursorMeta,
        baseCursor.isAcceptableOrUnknown(data['base_cursor']!, _baseCursorMeta),
      );
    }
    if (data.containsKey('revision')) {
      context.handle(
        _revisionMeta,
        revision.isAcceptableOrUnknown(data['revision']!, _revisionMeta),
      );
    }
    if (data.containsKey('next_page_token')) {
      context.handle(
        _nextPageTokenMeta,
        nextPageToken.isAcceptableOrUnknown(
          data['next_page_token']!,
          _nextPageTokenMeta,
        ),
      );
    }
    if (data.containsKey('page_count')) {
      context.handle(
        _pageCountMeta,
        pageCount.isAcceptableOrUnknown(data['page_count']!, _pageCountMeta),
      );
    }
    if (data.containsKey('complete')) {
      context.handle(
        _completeMeta,
        complete.isAcceptableOrUnknown(data['complete']!, _completeMeta),
      );
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  Download map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return Download(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}id'],
      )!,
      incremental: attachedDatabase.typeMapping.read(
        DriftSqlType.bool,
        data['${effectivePrefix}incremental'],
      )!,
      baseCursor: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}base_cursor'],
      ),
      revision: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}revision'],
      ),
      nextPageToken: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}next_page_token'],
      ),
      pageCount: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}page_count'],
      )!,
      complete: attachedDatabase.typeMapping.read(
        DriftSqlType.bool,
        data['${effectivePrefix}complete'],
      )!,
    );
  }

  @override
  $DownloadsTable createAlias(String alias) {
    return $DownloadsTable(attachedDatabase, alias);
  }
}

class Download extends DataClass implements Insertable<Download> {
  final int id;
  final bool incremental;
  final String? baseCursor;
  final String? revision;
  final String? nextPageToken;
  final int pageCount;
  final bool complete;
  const Download({
    required this.id,
    required this.incremental,
    this.baseCursor,
    this.revision,
    this.nextPageToken,
    required this.pageCount,
    required this.complete,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<int>(id);
    map['incremental'] = Variable<bool>(incremental);
    if (!nullToAbsent || baseCursor != null) {
      map['base_cursor'] = Variable<String>(baseCursor);
    }
    if (!nullToAbsent || revision != null) {
      map['revision'] = Variable<String>(revision);
    }
    if (!nullToAbsent || nextPageToken != null) {
      map['next_page_token'] = Variable<String>(nextPageToken);
    }
    map['page_count'] = Variable<int>(pageCount);
    map['complete'] = Variable<bool>(complete);
    return map;
  }

  DownloadsCompanion toCompanion(bool nullToAbsent) {
    return DownloadsCompanion(
      id: Value(id),
      incremental: Value(incremental),
      baseCursor: baseCursor == null && nullToAbsent
          ? const Value.absent()
          : Value(baseCursor),
      revision: revision == null && nullToAbsent
          ? const Value.absent()
          : Value(revision),
      nextPageToken: nextPageToken == null && nullToAbsent
          ? const Value.absent()
          : Value(nextPageToken),
      pageCount: Value(pageCount),
      complete: Value(complete),
    );
  }

  factory Download.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return Download(
      id: serializer.fromJson<int>(json['id']),
      incremental: serializer.fromJson<bool>(json['incremental']),
      baseCursor: serializer.fromJson<String?>(json['baseCursor']),
      revision: serializer.fromJson<String?>(json['revision']),
      nextPageToken: serializer.fromJson<String?>(json['nextPageToken']),
      pageCount: serializer.fromJson<int>(json['pageCount']),
      complete: serializer.fromJson<bool>(json['complete']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<int>(id),
      'incremental': serializer.toJson<bool>(incremental),
      'baseCursor': serializer.toJson<String?>(baseCursor),
      'revision': serializer.toJson<String?>(revision),
      'nextPageToken': serializer.toJson<String?>(nextPageToken),
      'pageCount': serializer.toJson<int>(pageCount),
      'complete': serializer.toJson<bool>(complete),
    };
  }

  Download copyWith({
    int? id,
    bool? incremental,
    Value<String?> baseCursor = const Value.absent(),
    Value<String?> revision = const Value.absent(),
    Value<String?> nextPageToken = const Value.absent(),
    int? pageCount,
    bool? complete,
  }) => Download(
    id: id ?? this.id,
    incremental: incremental ?? this.incremental,
    baseCursor: baseCursor.present ? baseCursor.value : this.baseCursor,
    revision: revision.present ? revision.value : this.revision,
    nextPageToken: nextPageToken.present
        ? nextPageToken.value
        : this.nextPageToken,
    pageCount: pageCount ?? this.pageCount,
    complete: complete ?? this.complete,
  );
  Download copyWithCompanion(DownloadsCompanion data) {
    return Download(
      id: data.id.present ? data.id.value : this.id,
      incremental: data.incremental.present
          ? data.incremental.value
          : this.incremental,
      baseCursor: data.baseCursor.present
          ? data.baseCursor.value
          : this.baseCursor,
      revision: data.revision.present ? data.revision.value : this.revision,
      nextPageToken: data.nextPageToken.present
          ? data.nextPageToken.value
          : this.nextPageToken,
      pageCount: data.pageCount.present ? data.pageCount.value : this.pageCount,
      complete: data.complete.present ? data.complete.value : this.complete,
    );
  }

  @override
  String toString() {
    return (StringBuffer('Download(')
          ..write('id: $id, ')
          ..write('incremental: $incremental, ')
          ..write('baseCursor: $baseCursor, ')
          ..write('revision: $revision, ')
          ..write('nextPageToken: $nextPageToken, ')
          ..write('pageCount: $pageCount, ')
          ..write('complete: $complete')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    id,
    incremental,
    baseCursor,
    revision,
    nextPageToken,
    pageCount,
    complete,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is Download &&
          other.id == this.id &&
          other.incremental == this.incremental &&
          other.baseCursor == this.baseCursor &&
          other.revision == this.revision &&
          other.nextPageToken == this.nextPageToken &&
          other.pageCount == this.pageCount &&
          other.complete == this.complete);
}

class DownloadsCompanion extends UpdateCompanion<Download> {
  final Value<int> id;
  final Value<bool> incremental;
  final Value<String?> baseCursor;
  final Value<String?> revision;
  final Value<String?> nextPageToken;
  final Value<int> pageCount;
  final Value<bool> complete;
  const DownloadsCompanion({
    this.id = const Value.absent(),
    this.incremental = const Value.absent(),
    this.baseCursor = const Value.absent(),
    this.revision = const Value.absent(),
    this.nextPageToken = const Value.absent(),
    this.pageCount = const Value.absent(),
    this.complete = const Value.absent(),
  });
  DownloadsCompanion.insert({
    this.id = const Value.absent(),
    required bool incremental,
    this.baseCursor = const Value.absent(),
    this.revision = const Value.absent(),
    this.nextPageToken = const Value.absent(),
    this.pageCount = const Value.absent(),
    this.complete = const Value.absent(),
  }) : incremental = Value(incremental);
  static Insertable<Download> custom({
    Expression<int>? id,
    Expression<bool>? incremental,
    Expression<String>? baseCursor,
    Expression<String>? revision,
    Expression<String>? nextPageToken,
    Expression<int>? pageCount,
    Expression<bool>? complete,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (incremental != null) 'incremental': incremental,
      if (baseCursor != null) 'base_cursor': baseCursor,
      if (revision != null) 'revision': revision,
      if (nextPageToken != null) 'next_page_token': nextPageToken,
      if (pageCount != null) 'page_count': pageCount,
      if (complete != null) 'complete': complete,
    });
  }

  DownloadsCompanion copyWith({
    Value<int>? id,
    Value<bool>? incremental,
    Value<String?>? baseCursor,
    Value<String?>? revision,
    Value<String?>? nextPageToken,
    Value<int>? pageCount,
    Value<bool>? complete,
  }) {
    return DownloadsCompanion(
      id: id ?? this.id,
      incremental: incremental ?? this.incremental,
      baseCursor: baseCursor ?? this.baseCursor,
      revision: revision ?? this.revision,
      nextPageToken: nextPageToken ?? this.nextPageToken,
      pageCount: pageCount ?? this.pageCount,
      complete: complete ?? this.complete,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<int>(id.value);
    }
    if (incremental.present) {
      map['incremental'] = Variable<bool>(incremental.value);
    }
    if (baseCursor.present) {
      map['base_cursor'] = Variable<String>(baseCursor.value);
    }
    if (revision.present) {
      map['revision'] = Variable<String>(revision.value);
    }
    if (nextPageToken.present) {
      map['next_page_token'] = Variable<String>(nextPageToken.value);
    }
    if (pageCount.present) {
      map['page_count'] = Variable<int>(pageCount.value);
    }
    if (complete.present) {
      map['complete'] = Variable<bool>(complete.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('DownloadsCompanion(')
          ..write('id: $id, ')
          ..write('incremental: $incremental, ')
          ..write('baseCursor: $baseCursor, ')
          ..write('revision: $revision, ')
          ..write('nextPageToken: $nextPageToken, ')
          ..write('pageCount: $pageCount, ')
          ..write('complete: $complete')
          ..write(')'))
        .toString();
  }
}

class $DownloadPagesTable extends DownloadPages
    with TableInfo<$DownloadPagesTable, DownloadPage> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $DownloadPagesTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _ordinalMeta = const VerificationMeta(
    'ordinal',
  );
  @override
  late final GeneratedColumn<int> ordinal = GeneratedColumn<int>(
    'ordinal',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _payloadMeta = const VerificationMeta(
    'payload',
  );
  @override
  late final GeneratedColumn<String> payload = GeneratedColumn<String>(
    'payload',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [ordinal, payload];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'download_pages';
  @override
  VerificationContext validateIntegrity(
    Insertable<DownloadPage> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('ordinal')) {
      context.handle(
        _ordinalMeta,
        ordinal.isAcceptableOrUnknown(data['ordinal']!, _ordinalMeta),
      );
    }
    if (data.containsKey('payload')) {
      context.handle(
        _payloadMeta,
        payload.isAcceptableOrUnknown(data['payload']!, _payloadMeta),
      );
    } else if (isInserting) {
      context.missing(_payloadMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {ordinal};
  @override
  DownloadPage map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return DownloadPage(
      ordinal: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}ordinal'],
      )!,
      payload: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}payload'],
      )!,
    );
  }

  @override
  $DownloadPagesTable createAlias(String alias) {
    return $DownloadPagesTable(attachedDatabase, alias);
  }
}

class DownloadPage extends DataClass implements Insertable<DownloadPage> {
  final int ordinal;
  final String payload;
  const DownloadPage({required this.ordinal, required this.payload});
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['ordinal'] = Variable<int>(ordinal);
    map['payload'] = Variable<String>(payload);
    return map;
  }

  DownloadPagesCompanion toCompanion(bool nullToAbsent) {
    return DownloadPagesCompanion(
      ordinal: Value(ordinal),
      payload: Value(payload),
    );
  }

  factory DownloadPage.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return DownloadPage(
      ordinal: serializer.fromJson<int>(json['ordinal']),
      payload: serializer.fromJson<String>(json['payload']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'ordinal': serializer.toJson<int>(ordinal),
      'payload': serializer.toJson<String>(payload),
    };
  }

  DownloadPage copyWith({int? ordinal, String? payload}) => DownloadPage(
    ordinal: ordinal ?? this.ordinal,
    payload: payload ?? this.payload,
  );
  DownloadPage copyWithCompanion(DownloadPagesCompanion data) {
    return DownloadPage(
      ordinal: data.ordinal.present ? data.ordinal.value : this.ordinal,
      payload: data.payload.present ? data.payload.value : this.payload,
    );
  }

  @override
  String toString() {
    return (StringBuffer('DownloadPage(')
          ..write('ordinal: $ordinal, ')
          ..write('payload: $payload')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(ordinal, payload);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is DownloadPage &&
          other.ordinal == this.ordinal &&
          other.payload == this.payload);
}

class DownloadPagesCompanion extends UpdateCompanion<DownloadPage> {
  final Value<int> ordinal;
  final Value<String> payload;
  const DownloadPagesCompanion({
    this.ordinal = const Value.absent(),
    this.payload = const Value.absent(),
  });
  DownloadPagesCompanion.insert({
    this.ordinal = const Value.absent(),
    required String payload,
  }) : payload = Value(payload);
  static Insertable<DownloadPage> custom({
    Expression<int>? ordinal,
    Expression<String>? payload,
  }) {
    return RawValuesInsertable({
      if (ordinal != null) 'ordinal': ordinal,
      if (payload != null) 'payload': payload,
    });
  }

  DownloadPagesCompanion copyWith({
    Value<int>? ordinal,
    Value<String>? payload,
  }) {
    return DownloadPagesCompanion(
      ordinal: ordinal ?? this.ordinal,
      payload: payload ?? this.payload,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (ordinal.present) {
      map['ordinal'] = Variable<int>(ordinal.value);
    }
    if (payload.present) {
      map['payload'] = Variable<String>(payload.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('DownloadPagesCompanion(')
          ..write('ordinal: $ordinal, ')
          ..write('payload: $payload')
          ..write(')'))
        .toString();
  }
}

class $LocalEffectsTable extends LocalEffects
    with TableInfo<$LocalEffectsTable, LocalEffect> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $LocalEffectsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _operationIdMeta = const VerificationMeta(
    'operationId',
  );
  @override
  late final GeneratedColumn<String> operationId = GeneratedColumn<String>(
    'operation_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _entityIdMeta = const VerificationMeta(
    'entityId',
  );
  @override
  late final GeneratedColumn<String> entityId = GeneratedColumn<String>(
    'entity_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _kindMeta = const VerificationMeta('kind');
  @override
  late final GeneratedColumn<String> kind = GeneratedColumn<String>(
    'kind',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _stockDeltaMeta = const VerificationMeta(
    'stockDelta',
  );
  @override
  late final GeneratedColumn<int> stockDelta = GeneratedColumn<int>(
    'stock_delta',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
    defaultValue: const Constant(0),
  );
  static const VerificationMeta _debtDeltaMeta = const VerificationMeta(
    'debtDelta',
  );
  @override
  late final GeneratedColumn<int> debtDelta = GeneratedColumn<int>(
    'debt_delta',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
    defaultValue: const Constant(0),
  );
  static const VerificationMeta _paymentDeltaMeta = const VerificationMeta(
    'paymentDelta',
  );
  @override
  late final GeneratedColumn<int> paymentDelta = GeneratedColumn<int>(
    'payment_delta',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
    defaultValue: const Constant(0),
  );
  static const VerificationMeta _reflectedMeta = const VerificationMeta(
    'reflected',
  );
  @override
  late final GeneratedColumn<bool> reflected = GeneratedColumn<bool>(
    'reflected',
    aliasedName,
    false,
    type: DriftSqlType.bool,
    requiredDuringInsert: false,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'CHECK ("reflected" IN (0, 1))',
    ),
    defaultValue: const Constant(false),
  );
  static const VerificationMeta _originalStatusMeta = const VerificationMeta(
    'originalStatus',
  );
  @override
  late final GeneratedColumn<String> originalStatus = GeneratedColumn<String>(
    'original_status',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  @override
  List<GeneratedColumn> get $columns => [
    operationId,
    entityId,
    kind,
    stockDelta,
    debtDelta,
    paymentDelta,
    reflected,
    originalStatus,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'local_effects';
  @override
  VerificationContext validateIntegrity(
    Insertable<LocalEffect> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('operation_id')) {
      context.handle(
        _operationIdMeta,
        operationId.isAcceptableOrUnknown(
          data['operation_id']!,
          _operationIdMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_operationIdMeta);
    }
    if (data.containsKey('entity_id')) {
      context.handle(
        _entityIdMeta,
        entityId.isAcceptableOrUnknown(data['entity_id']!, _entityIdMeta),
      );
    } else if (isInserting) {
      context.missing(_entityIdMeta);
    }
    if (data.containsKey('kind')) {
      context.handle(
        _kindMeta,
        kind.isAcceptableOrUnknown(data['kind']!, _kindMeta),
      );
    } else if (isInserting) {
      context.missing(_kindMeta);
    }
    if (data.containsKey('stock_delta')) {
      context.handle(
        _stockDeltaMeta,
        stockDelta.isAcceptableOrUnknown(data['stock_delta']!, _stockDeltaMeta),
      );
    }
    if (data.containsKey('debt_delta')) {
      context.handle(
        _debtDeltaMeta,
        debtDelta.isAcceptableOrUnknown(data['debt_delta']!, _debtDeltaMeta),
      );
    }
    if (data.containsKey('payment_delta')) {
      context.handle(
        _paymentDeltaMeta,
        paymentDelta.isAcceptableOrUnknown(
          data['payment_delta']!,
          _paymentDeltaMeta,
        ),
      );
    }
    if (data.containsKey('reflected')) {
      context.handle(
        _reflectedMeta,
        reflected.isAcceptableOrUnknown(data['reflected']!, _reflectedMeta),
      );
    }
    if (data.containsKey('original_status')) {
      context.handle(
        _originalStatusMeta,
        originalStatus.isAcceptableOrUnknown(
          data['original_status']!,
          _originalStatusMeta,
        ),
      );
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {operationId, entityId, kind};
  @override
  LocalEffect map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return LocalEffect(
      operationId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}operation_id'],
      )!,
      entityId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}entity_id'],
      )!,
      kind: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}kind'],
      )!,
      stockDelta: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}stock_delta'],
      )!,
      debtDelta: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}debt_delta'],
      )!,
      paymentDelta: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}payment_delta'],
      )!,
      reflected: attachedDatabase.typeMapping.read(
        DriftSqlType.bool,
        data['${effectivePrefix}reflected'],
      )!,
      originalStatus: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}original_status'],
      ),
    );
  }

  @override
  $LocalEffectsTable createAlias(String alias) {
    return $LocalEffectsTable(attachedDatabase, alias);
  }
}

class LocalEffect extends DataClass implements Insertable<LocalEffect> {
  final String operationId;
  final String entityId;
  final String kind;
  final int stockDelta;
  final int debtDelta;
  final int paymentDelta;
  final bool reflected;
  final String? originalStatus;
  const LocalEffect({
    required this.operationId,
    required this.entityId,
    required this.kind,
    required this.stockDelta,
    required this.debtDelta,
    required this.paymentDelta,
    required this.reflected,
    this.originalStatus,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['operation_id'] = Variable<String>(operationId);
    map['entity_id'] = Variable<String>(entityId);
    map['kind'] = Variable<String>(kind);
    map['stock_delta'] = Variable<int>(stockDelta);
    map['debt_delta'] = Variable<int>(debtDelta);
    map['payment_delta'] = Variable<int>(paymentDelta);
    map['reflected'] = Variable<bool>(reflected);
    if (!nullToAbsent || originalStatus != null) {
      map['original_status'] = Variable<String>(originalStatus);
    }
    return map;
  }

  LocalEffectsCompanion toCompanion(bool nullToAbsent) {
    return LocalEffectsCompanion(
      operationId: Value(operationId),
      entityId: Value(entityId),
      kind: Value(kind),
      stockDelta: Value(stockDelta),
      debtDelta: Value(debtDelta),
      paymentDelta: Value(paymentDelta),
      reflected: Value(reflected),
      originalStatus: originalStatus == null && nullToAbsent
          ? const Value.absent()
          : Value(originalStatus),
    );
  }

  factory LocalEffect.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return LocalEffect(
      operationId: serializer.fromJson<String>(json['operationId']),
      entityId: serializer.fromJson<String>(json['entityId']),
      kind: serializer.fromJson<String>(json['kind']),
      stockDelta: serializer.fromJson<int>(json['stockDelta']),
      debtDelta: serializer.fromJson<int>(json['debtDelta']),
      paymentDelta: serializer.fromJson<int>(json['paymentDelta']),
      reflected: serializer.fromJson<bool>(json['reflected']),
      originalStatus: serializer.fromJson<String?>(json['originalStatus']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'operationId': serializer.toJson<String>(operationId),
      'entityId': serializer.toJson<String>(entityId),
      'kind': serializer.toJson<String>(kind),
      'stockDelta': serializer.toJson<int>(stockDelta),
      'debtDelta': serializer.toJson<int>(debtDelta),
      'paymentDelta': serializer.toJson<int>(paymentDelta),
      'reflected': serializer.toJson<bool>(reflected),
      'originalStatus': serializer.toJson<String?>(originalStatus),
    };
  }

  LocalEffect copyWith({
    String? operationId,
    String? entityId,
    String? kind,
    int? stockDelta,
    int? debtDelta,
    int? paymentDelta,
    bool? reflected,
    Value<String?> originalStatus = const Value.absent(),
  }) => LocalEffect(
    operationId: operationId ?? this.operationId,
    entityId: entityId ?? this.entityId,
    kind: kind ?? this.kind,
    stockDelta: stockDelta ?? this.stockDelta,
    debtDelta: debtDelta ?? this.debtDelta,
    paymentDelta: paymentDelta ?? this.paymentDelta,
    reflected: reflected ?? this.reflected,
    originalStatus: originalStatus.present
        ? originalStatus.value
        : this.originalStatus,
  );
  LocalEffect copyWithCompanion(LocalEffectsCompanion data) {
    return LocalEffect(
      operationId: data.operationId.present
          ? data.operationId.value
          : this.operationId,
      entityId: data.entityId.present ? data.entityId.value : this.entityId,
      kind: data.kind.present ? data.kind.value : this.kind,
      stockDelta: data.stockDelta.present
          ? data.stockDelta.value
          : this.stockDelta,
      debtDelta: data.debtDelta.present ? data.debtDelta.value : this.debtDelta,
      paymentDelta: data.paymentDelta.present
          ? data.paymentDelta.value
          : this.paymentDelta,
      reflected: data.reflected.present ? data.reflected.value : this.reflected,
      originalStatus: data.originalStatus.present
          ? data.originalStatus.value
          : this.originalStatus,
    );
  }

  @override
  String toString() {
    return (StringBuffer('LocalEffect(')
          ..write('operationId: $operationId, ')
          ..write('entityId: $entityId, ')
          ..write('kind: $kind, ')
          ..write('stockDelta: $stockDelta, ')
          ..write('debtDelta: $debtDelta, ')
          ..write('paymentDelta: $paymentDelta, ')
          ..write('reflected: $reflected, ')
          ..write('originalStatus: $originalStatus')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    operationId,
    entityId,
    kind,
    stockDelta,
    debtDelta,
    paymentDelta,
    reflected,
    originalStatus,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is LocalEffect &&
          other.operationId == this.operationId &&
          other.entityId == this.entityId &&
          other.kind == this.kind &&
          other.stockDelta == this.stockDelta &&
          other.debtDelta == this.debtDelta &&
          other.paymentDelta == this.paymentDelta &&
          other.reflected == this.reflected &&
          other.originalStatus == this.originalStatus);
}

class LocalEffectsCompanion extends UpdateCompanion<LocalEffect> {
  final Value<String> operationId;
  final Value<String> entityId;
  final Value<String> kind;
  final Value<int> stockDelta;
  final Value<int> debtDelta;
  final Value<int> paymentDelta;
  final Value<bool> reflected;
  final Value<String?> originalStatus;
  final Value<int> rowid;
  const LocalEffectsCompanion({
    this.operationId = const Value.absent(),
    this.entityId = const Value.absent(),
    this.kind = const Value.absent(),
    this.stockDelta = const Value.absent(),
    this.debtDelta = const Value.absent(),
    this.paymentDelta = const Value.absent(),
    this.reflected = const Value.absent(),
    this.originalStatus = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  LocalEffectsCompanion.insert({
    required String operationId,
    required String entityId,
    required String kind,
    this.stockDelta = const Value.absent(),
    this.debtDelta = const Value.absent(),
    this.paymentDelta = const Value.absent(),
    this.reflected = const Value.absent(),
    this.originalStatus = const Value.absent(),
    this.rowid = const Value.absent(),
  }) : operationId = Value(operationId),
       entityId = Value(entityId),
       kind = Value(kind);
  static Insertable<LocalEffect> custom({
    Expression<String>? operationId,
    Expression<String>? entityId,
    Expression<String>? kind,
    Expression<int>? stockDelta,
    Expression<int>? debtDelta,
    Expression<int>? paymentDelta,
    Expression<bool>? reflected,
    Expression<String>? originalStatus,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (operationId != null) 'operation_id': operationId,
      if (entityId != null) 'entity_id': entityId,
      if (kind != null) 'kind': kind,
      if (stockDelta != null) 'stock_delta': stockDelta,
      if (debtDelta != null) 'debt_delta': debtDelta,
      if (paymentDelta != null) 'payment_delta': paymentDelta,
      if (reflected != null) 'reflected': reflected,
      if (originalStatus != null) 'original_status': originalStatus,
      if (rowid != null) 'rowid': rowid,
    });
  }

  LocalEffectsCompanion copyWith({
    Value<String>? operationId,
    Value<String>? entityId,
    Value<String>? kind,
    Value<int>? stockDelta,
    Value<int>? debtDelta,
    Value<int>? paymentDelta,
    Value<bool>? reflected,
    Value<String?>? originalStatus,
    Value<int>? rowid,
  }) {
    return LocalEffectsCompanion(
      operationId: operationId ?? this.operationId,
      entityId: entityId ?? this.entityId,
      kind: kind ?? this.kind,
      stockDelta: stockDelta ?? this.stockDelta,
      debtDelta: debtDelta ?? this.debtDelta,
      paymentDelta: paymentDelta ?? this.paymentDelta,
      reflected: reflected ?? this.reflected,
      originalStatus: originalStatus ?? this.originalStatus,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (operationId.present) {
      map['operation_id'] = Variable<String>(operationId.value);
    }
    if (entityId.present) {
      map['entity_id'] = Variable<String>(entityId.value);
    }
    if (kind.present) {
      map['kind'] = Variable<String>(kind.value);
    }
    if (stockDelta.present) {
      map['stock_delta'] = Variable<int>(stockDelta.value);
    }
    if (debtDelta.present) {
      map['debt_delta'] = Variable<int>(debtDelta.value);
    }
    if (paymentDelta.present) {
      map['payment_delta'] = Variable<int>(paymentDelta.value);
    }
    if (reflected.present) {
      map['reflected'] = Variable<bool>(reflected.value);
    }
    if (originalStatus.present) {
      map['original_status'] = Variable<String>(originalStatus.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('LocalEffectsCompanion(')
          ..write('operationId: $operationId, ')
          ..write('entityId: $entityId, ')
          ..write('kind: $kind, ')
          ..write('stockDelta: $stockDelta, ')
          ..write('debtDelta: $debtDelta, ')
          ..write('paymentDelta: $paymentDelta, ')
          ..write('reflected: $reflected, ')
          ..write('originalStatus: $originalStatus, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $LocalSalesTable extends LocalSales
    with TableInfo<$LocalSalesTable, LocalSale> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $LocalSalesTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<String> id = GeneratedColumn<String>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _operationIdMeta = const VerificationMeta(
    'operationId',
  );
  @override
  late final GeneratedColumn<String> operationId = GeneratedColumn<String>(
    'operation_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
    defaultConstraints: GeneratedColumn.constraintIsAlways('UNIQUE'),
  );
  static const VerificationMeta _contextIdMeta = const VerificationMeta(
    'contextId',
  );
  @override
  late final GeneratedColumn<String> contextId = GeneratedColumn<String>(
    'context_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _sequenceMeta = const VerificationMeta(
    'sequence',
  );
  @override
  late final GeneratedColumn<int> sequence = GeneratedColumn<int>(
    'sequence',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
    defaultConstraints: GeneratedColumn.constraintIsAlways('UNIQUE'),
  );
  static const VerificationMeta _localFolioMeta = const VerificationMeta(
    'localFolio',
  );
  @override
  late final GeneratedColumn<String> localFolio = GeneratedColumn<String>(
    'local_folio',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
    defaultConstraints: GeneratedColumn.constraintIsAlways('UNIQUE'),
  );
  static const VerificationMeta _serverFolioMeta = const VerificationMeta(
    'serverFolio',
  );
  @override
  late final GeneratedColumn<String> serverFolio = GeneratedColumn<String>(
    'server_folio',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _customerIdMeta = const VerificationMeta(
    'customerId',
  );
  @override
  late final GeneratedColumn<String> customerId = GeneratedColumn<String>(
    'customer_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _customerNameMeta = const VerificationMeta(
    'customerName',
  );
  @override
  late final GeneratedColumn<String> customerName = GeneratedColumn<String>(
    'customer_name',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _branchNameMeta = const VerificationMeta(
    'branchName',
  );
  @override
  late final GeneratedColumn<String> branchName = GeneratedColumn<String>(
    'branch_name',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _operatorNameMeta = const VerificationMeta(
    'operatorName',
  );
  @override
  late final GeneratedColumn<String> operatorName = GeneratedColumn<String>(
    'operator_name',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _saleTypeMeta = const VerificationMeta(
    'saleType',
  );
  @override
  late final GeneratedColumn<String> saleType = GeneratedColumn<String>(
    'sale_type',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _discountBasisPointsMeta =
      const VerificationMeta('discountBasisPoints');
  @override
  late final GeneratedColumn<int> discountBasisPoints = GeneratedColumn<int>(
    'discount_basis_points',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _totalCentsMeta = const VerificationMeta(
    'totalCents',
  );
  @override
  late final GeneratedColumn<int> totalCents = GeneratedColumn<int>(
    'total_cents',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _appliedCentsMeta = const VerificationMeta(
    'appliedCents',
  );
  @override
  late final GeneratedColumn<int> appliedCents = GeneratedColumn<int>(
    'applied_cents',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _receivedCentsMeta = const VerificationMeta(
    'receivedCents',
  );
  @override
  late final GeneratedColumn<int> receivedCents = GeneratedColumn<int>(
    'received_cents',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _changeCentsMeta = const VerificationMeta(
    'changeCents',
  );
  @override
  late final GeneratedColumn<int> changeCents = GeneratedColumn<int>(
    'change_cents',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _balanceCentsMeta = const VerificationMeta(
    'balanceCents',
  );
  @override
  late final GeneratedColumn<int> balanceCents = GeneratedColumn<int>(
    'balance_cents',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _paidMeta = const VerificationMeta('paid');
  @override
  late final GeneratedColumn<bool> paid = GeneratedColumn<bool>(
    'paid',
    aliasedName,
    false,
    type: DriftSqlType.bool,
    requiredDuringInsert: true,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'CHECK ("paid" IN (0, 1))',
    ),
  );
  static const VerificationMeta _statusMeta = const VerificationMeta('status');
  @override
  late final GeneratedColumn<String> status = GeneratedColumn<String>(
    'status',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
    defaultValue: const Constant('pending_sync'),
  );
  static const VerificationMeta _occurredAtMeta = const VerificationMeta(
    'occurredAt',
  );
  @override
  late final GeneratedColumn<String> occurredAt = GeneratedColumn<String>(
    'occurred_at',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _offlineLeaseIdMeta = const VerificationMeta(
    'offlineLeaseId',
  );
  @override
  late final GeneratedColumn<String> offlineLeaseId = GeneratedColumn<String>(
    'offline_lease_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _catalogRevisionMeta = const VerificationMeta(
    'catalogRevision',
  );
  @override
  late final GeneratedColumn<String> catalogRevision = GeneratedColumn<String>(
    'catalog_revision',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _requestHashMeta = const VerificationMeta(
    'requestHash',
  );
  @override
  late final GeneratedColumn<String> requestHash = GeneratedColumn<String>(
    'request_hash',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [
    id,
    operationId,
    contextId,
    sequence,
    localFolio,
    serverFolio,
    customerId,
    customerName,
    branchName,
    operatorName,
    saleType,
    discountBasisPoints,
    totalCents,
    appliedCents,
    receivedCents,
    changeCents,
    balanceCents,
    paid,
    status,
    occurredAt,
    offlineLeaseId,
    catalogRevision,
    requestHash,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'local_sales';
  @override
  VerificationContext validateIntegrity(
    Insertable<LocalSale> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    } else if (isInserting) {
      context.missing(_idMeta);
    }
    if (data.containsKey('operation_id')) {
      context.handle(
        _operationIdMeta,
        operationId.isAcceptableOrUnknown(
          data['operation_id']!,
          _operationIdMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_operationIdMeta);
    }
    if (data.containsKey('context_id')) {
      context.handle(
        _contextIdMeta,
        contextId.isAcceptableOrUnknown(data['context_id']!, _contextIdMeta),
      );
    } else if (isInserting) {
      context.missing(_contextIdMeta);
    }
    if (data.containsKey('sequence')) {
      context.handle(
        _sequenceMeta,
        sequence.isAcceptableOrUnknown(data['sequence']!, _sequenceMeta),
      );
    } else if (isInserting) {
      context.missing(_sequenceMeta);
    }
    if (data.containsKey('local_folio')) {
      context.handle(
        _localFolioMeta,
        localFolio.isAcceptableOrUnknown(data['local_folio']!, _localFolioMeta),
      );
    } else if (isInserting) {
      context.missing(_localFolioMeta);
    }
    if (data.containsKey('server_folio')) {
      context.handle(
        _serverFolioMeta,
        serverFolio.isAcceptableOrUnknown(
          data['server_folio']!,
          _serverFolioMeta,
        ),
      );
    }
    if (data.containsKey('customer_id')) {
      context.handle(
        _customerIdMeta,
        customerId.isAcceptableOrUnknown(data['customer_id']!, _customerIdMeta),
      );
    } else if (isInserting) {
      context.missing(_customerIdMeta);
    }
    if (data.containsKey('customer_name')) {
      context.handle(
        _customerNameMeta,
        customerName.isAcceptableOrUnknown(
          data['customer_name']!,
          _customerNameMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_customerNameMeta);
    }
    if (data.containsKey('branch_name')) {
      context.handle(
        _branchNameMeta,
        branchName.isAcceptableOrUnknown(data['branch_name']!, _branchNameMeta),
      );
    } else if (isInserting) {
      context.missing(_branchNameMeta);
    }
    if (data.containsKey('operator_name')) {
      context.handle(
        _operatorNameMeta,
        operatorName.isAcceptableOrUnknown(
          data['operator_name']!,
          _operatorNameMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_operatorNameMeta);
    }
    if (data.containsKey('sale_type')) {
      context.handle(
        _saleTypeMeta,
        saleType.isAcceptableOrUnknown(data['sale_type']!, _saleTypeMeta),
      );
    } else if (isInserting) {
      context.missing(_saleTypeMeta);
    }
    if (data.containsKey('discount_basis_points')) {
      context.handle(
        _discountBasisPointsMeta,
        discountBasisPoints.isAcceptableOrUnknown(
          data['discount_basis_points']!,
          _discountBasisPointsMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_discountBasisPointsMeta);
    }
    if (data.containsKey('total_cents')) {
      context.handle(
        _totalCentsMeta,
        totalCents.isAcceptableOrUnknown(data['total_cents']!, _totalCentsMeta),
      );
    } else if (isInserting) {
      context.missing(_totalCentsMeta);
    }
    if (data.containsKey('applied_cents')) {
      context.handle(
        _appliedCentsMeta,
        appliedCents.isAcceptableOrUnknown(
          data['applied_cents']!,
          _appliedCentsMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_appliedCentsMeta);
    }
    if (data.containsKey('received_cents')) {
      context.handle(
        _receivedCentsMeta,
        receivedCents.isAcceptableOrUnknown(
          data['received_cents']!,
          _receivedCentsMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_receivedCentsMeta);
    }
    if (data.containsKey('change_cents')) {
      context.handle(
        _changeCentsMeta,
        changeCents.isAcceptableOrUnknown(
          data['change_cents']!,
          _changeCentsMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_changeCentsMeta);
    }
    if (data.containsKey('balance_cents')) {
      context.handle(
        _balanceCentsMeta,
        balanceCents.isAcceptableOrUnknown(
          data['balance_cents']!,
          _balanceCentsMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_balanceCentsMeta);
    }
    if (data.containsKey('paid')) {
      context.handle(
        _paidMeta,
        paid.isAcceptableOrUnknown(data['paid']!, _paidMeta),
      );
    } else if (isInserting) {
      context.missing(_paidMeta);
    }
    if (data.containsKey('status')) {
      context.handle(
        _statusMeta,
        status.isAcceptableOrUnknown(data['status']!, _statusMeta),
      );
    }
    if (data.containsKey('occurred_at')) {
      context.handle(
        _occurredAtMeta,
        occurredAt.isAcceptableOrUnknown(data['occurred_at']!, _occurredAtMeta),
      );
    } else if (isInserting) {
      context.missing(_occurredAtMeta);
    }
    if (data.containsKey('offline_lease_id')) {
      context.handle(
        _offlineLeaseIdMeta,
        offlineLeaseId.isAcceptableOrUnknown(
          data['offline_lease_id']!,
          _offlineLeaseIdMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_offlineLeaseIdMeta);
    }
    if (data.containsKey('catalog_revision')) {
      context.handle(
        _catalogRevisionMeta,
        catalogRevision.isAcceptableOrUnknown(
          data['catalog_revision']!,
          _catalogRevisionMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_catalogRevisionMeta);
    }
    if (data.containsKey('request_hash')) {
      context.handle(
        _requestHashMeta,
        requestHash.isAcceptableOrUnknown(
          data['request_hash']!,
          _requestHashMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_requestHashMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  LocalSale map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return LocalSale(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}id'],
      )!,
      operationId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}operation_id'],
      )!,
      contextId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}context_id'],
      )!,
      sequence: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}sequence'],
      )!,
      localFolio: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}local_folio'],
      )!,
      serverFolio: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}server_folio'],
      ),
      customerId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}customer_id'],
      )!,
      customerName: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}customer_name'],
      )!,
      branchName: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}branch_name'],
      )!,
      operatorName: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}operator_name'],
      )!,
      saleType: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}sale_type'],
      )!,
      discountBasisPoints: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}discount_basis_points'],
      )!,
      totalCents: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}total_cents'],
      )!,
      appliedCents: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}applied_cents'],
      )!,
      receivedCents: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}received_cents'],
      )!,
      changeCents: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}change_cents'],
      )!,
      balanceCents: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}balance_cents'],
      )!,
      paid: attachedDatabase.typeMapping.read(
        DriftSqlType.bool,
        data['${effectivePrefix}paid'],
      )!,
      status: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}status'],
      )!,
      occurredAt: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}occurred_at'],
      )!,
      offlineLeaseId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}offline_lease_id'],
      )!,
      catalogRevision: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}catalog_revision'],
      )!,
      requestHash: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}request_hash'],
      )!,
    );
  }

  @override
  $LocalSalesTable createAlias(String alias) {
    return $LocalSalesTable(attachedDatabase, alias);
  }
}

class LocalSale extends DataClass implements Insertable<LocalSale> {
  final String id;
  final String operationId;
  final String contextId;
  final int sequence;
  final String localFolio;
  final String? serverFolio;
  final String customerId;
  final String customerName;
  final String branchName;
  final String operatorName;
  final String saleType;
  final int discountBasisPoints;
  final int totalCents;
  final int appliedCents;
  final int receivedCents;
  final int changeCents;
  final int balanceCents;
  final bool paid;
  final String status;
  final String occurredAt;
  final String offlineLeaseId;
  final String catalogRevision;
  final String requestHash;
  const LocalSale({
    required this.id,
    required this.operationId,
    required this.contextId,
    required this.sequence,
    required this.localFolio,
    this.serverFolio,
    required this.customerId,
    required this.customerName,
    required this.branchName,
    required this.operatorName,
    required this.saleType,
    required this.discountBasisPoints,
    required this.totalCents,
    required this.appliedCents,
    required this.receivedCents,
    required this.changeCents,
    required this.balanceCents,
    required this.paid,
    required this.status,
    required this.occurredAt,
    required this.offlineLeaseId,
    required this.catalogRevision,
    required this.requestHash,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<String>(id);
    map['operation_id'] = Variable<String>(operationId);
    map['context_id'] = Variable<String>(contextId);
    map['sequence'] = Variable<int>(sequence);
    map['local_folio'] = Variable<String>(localFolio);
    if (!nullToAbsent || serverFolio != null) {
      map['server_folio'] = Variable<String>(serverFolio);
    }
    map['customer_id'] = Variable<String>(customerId);
    map['customer_name'] = Variable<String>(customerName);
    map['branch_name'] = Variable<String>(branchName);
    map['operator_name'] = Variable<String>(operatorName);
    map['sale_type'] = Variable<String>(saleType);
    map['discount_basis_points'] = Variable<int>(discountBasisPoints);
    map['total_cents'] = Variable<int>(totalCents);
    map['applied_cents'] = Variable<int>(appliedCents);
    map['received_cents'] = Variable<int>(receivedCents);
    map['change_cents'] = Variable<int>(changeCents);
    map['balance_cents'] = Variable<int>(balanceCents);
    map['paid'] = Variable<bool>(paid);
    map['status'] = Variable<String>(status);
    map['occurred_at'] = Variable<String>(occurredAt);
    map['offline_lease_id'] = Variable<String>(offlineLeaseId);
    map['catalog_revision'] = Variable<String>(catalogRevision);
    map['request_hash'] = Variable<String>(requestHash);
    return map;
  }

  LocalSalesCompanion toCompanion(bool nullToAbsent) {
    return LocalSalesCompanion(
      id: Value(id),
      operationId: Value(operationId),
      contextId: Value(contextId),
      sequence: Value(sequence),
      localFolio: Value(localFolio),
      serverFolio: serverFolio == null && nullToAbsent
          ? const Value.absent()
          : Value(serverFolio),
      customerId: Value(customerId),
      customerName: Value(customerName),
      branchName: Value(branchName),
      operatorName: Value(operatorName),
      saleType: Value(saleType),
      discountBasisPoints: Value(discountBasisPoints),
      totalCents: Value(totalCents),
      appliedCents: Value(appliedCents),
      receivedCents: Value(receivedCents),
      changeCents: Value(changeCents),
      balanceCents: Value(balanceCents),
      paid: Value(paid),
      status: Value(status),
      occurredAt: Value(occurredAt),
      offlineLeaseId: Value(offlineLeaseId),
      catalogRevision: Value(catalogRevision),
      requestHash: Value(requestHash),
    );
  }

  factory LocalSale.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return LocalSale(
      id: serializer.fromJson<String>(json['id']),
      operationId: serializer.fromJson<String>(json['operationId']),
      contextId: serializer.fromJson<String>(json['contextId']),
      sequence: serializer.fromJson<int>(json['sequence']),
      localFolio: serializer.fromJson<String>(json['localFolio']),
      serverFolio: serializer.fromJson<String?>(json['serverFolio']),
      customerId: serializer.fromJson<String>(json['customerId']),
      customerName: serializer.fromJson<String>(json['customerName']),
      branchName: serializer.fromJson<String>(json['branchName']),
      operatorName: serializer.fromJson<String>(json['operatorName']),
      saleType: serializer.fromJson<String>(json['saleType']),
      discountBasisPoints: serializer.fromJson<int>(
        json['discountBasisPoints'],
      ),
      totalCents: serializer.fromJson<int>(json['totalCents']),
      appliedCents: serializer.fromJson<int>(json['appliedCents']),
      receivedCents: serializer.fromJson<int>(json['receivedCents']),
      changeCents: serializer.fromJson<int>(json['changeCents']),
      balanceCents: serializer.fromJson<int>(json['balanceCents']),
      paid: serializer.fromJson<bool>(json['paid']),
      status: serializer.fromJson<String>(json['status']),
      occurredAt: serializer.fromJson<String>(json['occurredAt']),
      offlineLeaseId: serializer.fromJson<String>(json['offlineLeaseId']),
      catalogRevision: serializer.fromJson<String>(json['catalogRevision']),
      requestHash: serializer.fromJson<String>(json['requestHash']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<String>(id),
      'operationId': serializer.toJson<String>(operationId),
      'contextId': serializer.toJson<String>(contextId),
      'sequence': serializer.toJson<int>(sequence),
      'localFolio': serializer.toJson<String>(localFolio),
      'serverFolio': serializer.toJson<String?>(serverFolio),
      'customerId': serializer.toJson<String>(customerId),
      'customerName': serializer.toJson<String>(customerName),
      'branchName': serializer.toJson<String>(branchName),
      'operatorName': serializer.toJson<String>(operatorName),
      'saleType': serializer.toJson<String>(saleType),
      'discountBasisPoints': serializer.toJson<int>(discountBasisPoints),
      'totalCents': serializer.toJson<int>(totalCents),
      'appliedCents': serializer.toJson<int>(appliedCents),
      'receivedCents': serializer.toJson<int>(receivedCents),
      'changeCents': serializer.toJson<int>(changeCents),
      'balanceCents': serializer.toJson<int>(balanceCents),
      'paid': serializer.toJson<bool>(paid),
      'status': serializer.toJson<String>(status),
      'occurredAt': serializer.toJson<String>(occurredAt),
      'offlineLeaseId': serializer.toJson<String>(offlineLeaseId),
      'catalogRevision': serializer.toJson<String>(catalogRevision),
      'requestHash': serializer.toJson<String>(requestHash),
    };
  }

  LocalSale copyWith({
    String? id,
    String? operationId,
    String? contextId,
    int? sequence,
    String? localFolio,
    Value<String?> serverFolio = const Value.absent(),
    String? customerId,
    String? customerName,
    String? branchName,
    String? operatorName,
    String? saleType,
    int? discountBasisPoints,
    int? totalCents,
    int? appliedCents,
    int? receivedCents,
    int? changeCents,
    int? balanceCents,
    bool? paid,
    String? status,
    String? occurredAt,
    String? offlineLeaseId,
    String? catalogRevision,
    String? requestHash,
  }) => LocalSale(
    id: id ?? this.id,
    operationId: operationId ?? this.operationId,
    contextId: contextId ?? this.contextId,
    sequence: sequence ?? this.sequence,
    localFolio: localFolio ?? this.localFolio,
    serverFolio: serverFolio.present ? serverFolio.value : this.serverFolio,
    customerId: customerId ?? this.customerId,
    customerName: customerName ?? this.customerName,
    branchName: branchName ?? this.branchName,
    operatorName: operatorName ?? this.operatorName,
    saleType: saleType ?? this.saleType,
    discountBasisPoints: discountBasisPoints ?? this.discountBasisPoints,
    totalCents: totalCents ?? this.totalCents,
    appliedCents: appliedCents ?? this.appliedCents,
    receivedCents: receivedCents ?? this.receivedCents,
    changeCents: changeCents ?? this.changeCents,
    balanceCents: balanceCents ?? this.balanceCents,
    paid: paid ?? this.paid,
    status: status ?? this.status,
    occurredAt: occurredAt ?? this.occurredAt,
    offlineLeaseId: offlineLeaseId ?? this.offlineLeaseId,
    catalogRevision: catalogRevision ?? this.catalogRevision,
    requestHash: requestHash ?? this.requestHash,
  );
  LocalSale copyWithCompanion(LocalSalesCompanion data) {
    return LocalSale(
      id: data.id.present ? data.id.value : this.id,
      operationId: data.operationId.present
          ? data.operationId.value
          : this.operationId,
      contextId: data.contextId.present ? data.contextId.value : this.contextId,
      sequence: data.sequence.present ? data.sequence.value : this.sequence,
      localFolio: data.localFolio.present
          ? data.localFolio.value
          : this.localFolio,
      serverFolio: data.serverFolio.present
          ? data.serverFolio.value
          : this.serverFolio,
      customerId: data.customerId.present
          ? data.customerId.value
          : this.customerId,
      customerName: data.customerName.present
          ? data.customerName.value
          : this.customerName,
      branchName: data.branchName.present
          ? data.branchName.value
          : this.branchName,
      operatorName: data.operatorName.present
          ? data.operatorName.value
          : this.operatorName,
      saleType: data.saleType.present ? data.saleType.value : this.saleType,
      discountBasisPoints: data.discountBasisPoints.present
          ? data.discountBasisPoints.value
          : this.discountBasisPoints,
      totalCents: data.totalCents.present
          ? data.totalCents.value
          : this.totalCents,
      appliedCents: data.appliedCents.present
          ? data.appliedCents.value
          : this.appliedCents,
      receivedCents: data.receivedCents.present
          ? data.receivedCents.value
          : this.receivedCents,
      changeCents: data.changeCents.present
          ? data.changeCents.value
          : this.changeCents,
      balanceCents: data.balanceCents.present
          ? data.balanceCents.value
          : this.balanceCents,
      paid: data.paid.present ? data.paid.value : this.paid,
      status: data.status.present ? data.status.value : this.status,
      occurredAt: data.occurredAt.present
          ? data.occurredAt.value
          : this.occurredAt,
      offlineLeaseId: data.offlineLeaseId.present
          ? data.offlineLeaseId.value
          : this.offlineLeaseId,
      catalogRevision: data.catalogRevision.present
          ? data.catalogRevision.value
          : this.catalogRevision,
      requestHash: data.requestHash.present
          ? data.requestHash.value
          : this.requestHash,
    );
  }

  @override
  String toString() {
    return (StringBuffer('LocalSale(')
          ..write('id: $id, ')
          ..write('operationId: $operationId, ')
          ..write('contextId: $contextId, ')
          ..write('sequence: $sequence, ')
          ..write('localFolio: $localFolio, ')
          ..write('serverFolio: $serverFolio, ')
          ..write('customerId: $customerId, ')
          ..write('customerName: $customerName, ')
          ..write('branchName: $branchName, ')
          ..write('operatorName: $operatorName, ')
          ..write('saleType: $saleType, ')
          ..write('discountBasisPoints: $discountBasisPoints, ')
          ..write('totalCents: $totalCents, ')
          ..write('appliedCents: $appliedCents, ')
          ..write('receivedCents: $receivedCents, ')
          ..write('changeCents: $changeCents, ')
          ..write('balanceCents: $balanceCents, ')
          ..write('paid: $paid, ')
          ..write('status: $status, ')
          ..write('occurredAt: $occurredAt, ')
          ..write('offlineLeaseId: $offlineLeaseId, ')
          ..write('catalogRevision: $catalogRevision, ')
          ..write('requestHash: $requestHash')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hashAll([
    id,
    operationId,
    contextId,
    sequence,
    localFolio,
    serverFolio,
    customerId,
    customerName,
    branchName,
    operatorName,
    saleType,
    discountBasisPoints,
    totalCents,
    appliedCents,
    receivedCents,
    changeCents,
    balanceCents,
    paid,
    status,
    occurredAt,
    offlineLeaseId,
    catalogRevision,
    requestHash,
  ]);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is LocalSale &&
          other.id == this.id &&
          other.operationId == this.operationId &&
          other.contextId == this.contextId &&
          other.sequence == this.sequence &&
          other.localFolio == this.localFolio &&
          other.serverFolio == this.serverFolio &&
          other.customerId == this.customerId &&
          other.customerName == this.customerName &&
          other.branchName == this.branchName &&
          other.operatorName == this.operatorName &&
          other.saleType == this.saleType &&
          other.discountBasisPoints == this.discountBasisPoints &&
          other.totalCents == this.totalCents &&
          other.appliedCents == this.appliedCents &&
          other.receivedCents == this.receivedCents &&
          other.changeCents == this.changeCents &&
          other.balanceCents == this.balanceCents &&
          other.paid == this.paid &&
          other.status == this.status &&
          other.occurredAt == this.occurredAt &&
          other.offlineLeaseId == this.offlineLeaseId &&
          other.catalogRevision == this.catalogRevision &&
          other.requestHash == this.requestHash);
}

class LocalSalesCompanion extends UpdateCompanion<LocalSale> {
  final Value<String> id;
  final Value<String> operationId;
  final Value<String> contextId;
  final Value<int> sequence;
  final Value<String> localFolio;
  final Value<String?> serverFolio;
  final Value<String> customerId;
  final Value<String> customerName;
  final Value<String> branchName;
  final Value<String> operatorName;
  final Value<String> saleType;
  final Value<int> discountBasisPoints;
  final Value<int> totalCents;
  final Value<int> appliedCents;
  final Value<int> receivedCents;
  final Value<int> changeCents;
  final Value<int> balanceCents;
  final Value<bool> paid;
  final Value<String> status;
  final Value<String> occurredAt;
  final Value<String> offlineLeaseId;
  final Value<String> catalogRevision;
  final Value<String> requestHash;
  final Value<int> rowid;
  const LocalSalesCompanion({
    this.id = const Value.absent(),
    this.operationId = const Value.absent(),
    this.contextId = const Value.absent(),
    this.sequence = const Value.absent(),
    this.localFolio = const Value.absent(),
    this.serverFolio = const Value.absent(),
    this.customerId = const Value.absent(),
    this.customerName = const Value.absent(),
    this.branchName = const Value.absent(),
    this.operatorName = const Value.absent(),
    this.saleType = const Value.absent(),
    this.discountBasisPoints = const Value.absent(),
    this.totalCents = const Value.absent(),
    this.appliedCents = const Value.absent(),
    this.receivedCents = const Value.absent(),
    this.changeCents = const Value.absent(),
    this.balanceCents = const Value.absent(),
    this.paid = const Value.absent(),
    this.status = const Value.absent(),
    this.occurredAt = const Value.absent(),
    this.offlineLeaseId = const Value.absent(),
    this.catalogRevision = const Value.absent(),
    this.requestHash = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  LocalSalesCompanion.insert({
    required String id,
    required String operationId,
    required String contextId,
    required int sequence,
    required String localFolio,
    this.serverFolio = const Value.absent(),
    required String customerId,
    required String customerName,
    required String branchName,
    required String operatorName,
    required String saleType,
    required int discountBasisPoints,
    required int totalCents,
    required int appliedCents,
    required int receivedCents,
    required int changeCents,
    required int balanceCents,
    required bool paid,
    this.status = const Value.absent(),
    required String occurredAt,
    required String offlineLeaseId,
    required String catalogRevision,
    required String requestHash,
    this.rowid = const Value.absent(),
  }) : id = Value(id),
       operationId = Value(operationId),
       contextId = Value(contextId),
       sequence = Value(sequence),
       localFolio = Value(localFolio),
       customerId = Value(customerId),
       customerName = Value(customerName),
       branchName = Value(branchName),
       operatorName = Value(operatorName),
       saleType = Value(saleType),
       discountBasisPoints = Value(discountBasisPoints),
       totalCents = Value(totalCents),
       appliedCents = Value(appliedCents),
       receivedCents = Value(receivedCents),
       changeCents = Value(changeCents),
       balanceCents = Value(balanceCents),
       paid = Value(paid),
       occurredAt = Value(occurredAt),
       offlineLeaseId = Value(offlineLeaseId),
       catalogRevision = Value(catalogRevision),
       requestHash = Value(requestHash);
  static Insertable<LocalSale> custom({
    Expression<String>? id,
    Expression<String>? operationId,
    Expression<String>? contextId,
    Expression<int>? sequence,
    Expression<String>? localFolio,
    Expression<String>? serverFolio,
    Expression<String>? customerId,
    Expression<String>? customerName,
    Expression<String>? branchName,
    Expression<String>? operatorName,
    Expression<String>? saleType,
    Expression<int>? discountBasisPoints,
    Expression<int>? totalCents,
    Expression<int>? appliedCents,
    Expression<int>? receivedCents,
    Expression<int>? changeCents,
    Expression<int>? balanceCents,
    Expression<bool>? paid,
    Expression<String>? status,
    Expression<String>? occurredAt,
    Expression<String>? offlineLeaseId,
    Expression<String>? catalogRevision,
    Expression<String>? requestHash,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (operationId != null) 'operation_id': operationId,
      if (contextId != null) 'context_id': contextId,
      if (sequence != null) 'sequence': sequence,
      if (localFolio != null) 'local_folio': localFolio,
      if (serverFolio != null) 'server_folio': serverFolio,
      if (customerId != null) 'customer_id': customerId,
      if (customerName != null) 'customer_name': customerName,
      if (branchName != null) 'branch_name': branchName,
      if (operatorName != null) 'operator_name': operatorName,
      if (saleType != null) 'sale_type': saleType,
      if (discountBasisPoints != null)
        'discount_basis_points': discountBasisPoints,
      if (totalCents != null) 'total_cents': totalCents,
      if (appliedCents != null) 'applied_cents': appliedCents,
      if (receivedCents != null) 'received_cents': receivedCents,
      if (changeCents != null) 'change_cents': changeCents,
      if (balanceCents != null) 'balance_cents': balanceCents,
      if (paid != null) 'paid': paid,
      if (status != null) 'status': status,
      if (occurredAt != null) 'occurred_at': occurredAt,
      if (offlineLeaseId != null) 'offline_lease_id': offlineLeaseId,
      if (catalogRevision != null) 'catalog_revision': catalogRevision,
      if (requestHash != null) 'request_hash': requestHash,
      if (rowid != null) 'rowid': rowid,
    });
  }

  LocalSalesCompanion copyWith({
    Value<String>? id,
    Value<String>? operationId,
    Value<String>? contextId,
    Value<int>? sequence,
    Value<String>? localFolio,
    Value<String?>? serverFolio,
    Value<String>? customerId,
    Value<String>? customerName,
    Value<String>? branchName,
    Value<String>? operatorName,
    Value<String>? saleType,
    Value<int>? discountBasisPoints,
    Value<int>? totalCents,
    Value<int>? appliedCents,
    Value<int>? receivedCents,
    Value<int>? changeCents,
    Value<int>? balanceCents,
    Value<bool>? paid,
    Value<String>? status,
    Value<String>? occurredAt,
    Value<String>? offlineLeaseId,
    Value<String>? catalogRevision,
    Value<String>? requestHash,
    Value<int>? rowid,
  }) {
    return LocalSalesCompanion(
      id: id ?? this.id,
      operationId: operationId ?? this.operationId,
      contextId: contextId ?? this.contextId,
      sequence: sequence ?? this.sequence,
      localFolio: localFolio ?? this.localFolio,
      serverFolio: serverFolio ?? this.serverFolio,
      customerId: customerId ?? this.customerId,
      customerName: customerName ?? this.customerName,
      branchName: branchName ?? this.branchName,
      operatorName: operatorName ?? this.operatorName,
      saleType: saleType ?? this.saleType,
      discountBasisPoints: discountBasisPoints ?? this.discountBasisPoints,
      totalCents: totalCents ?? this.totalCents,
      appliedCents: appliedCents ?? this.appliedCents,
      receivedCents: receivedCents ?? this.receivedCents,
      changeCents: changeCents ?? this.changeCents,
      balanceCents: balanceCents ?? this.balanceCents,
      paid: paid ?? this.paid,
      status: status ?? this.status,
      occurredAt: occurredAt ?? this.occurredAt,
      offlineLeaseId: offlineLeaseId ?? this.offlineLeaseId,
      catalogRevision: catalogRevision ?? this.catalogRevision,
      requestHash: requestHash ?? this.requestHash,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<String>(id.value);
    }
    if (operationId.present) {
      map['operation_id'] = Variable<String>(operationId.value);
    }
    if (contextId.present) {
      map['context_id'] = Variable<String>(contextId.value);
    }
    if (sequence.present) {
      map['sequence'] = Variable<int>(sequence.value);
    }
    if (localFolio.present) {
      map['local_folio'] = Variable<String>(localFolio.value);
    }
    if (serverFolio.present) {
      map['server_folio'] = Variable<String>(serverFolio.value);
    }
    if (customerId.present) {
      map['customer_id'] = Variable<String>(customerId.value);
    }
    if (customerName.present) {
      map['customer_name'] = Variable<String>(customerName.value);
    }
    if (branchName.present) {
      map['branch_name'] = Variable<String>(branchName.value);
    }
    if (operatorName.present) {
      map['operator_name'] = Variable<String>(operatorName.value);
    }
    if (saleType.present) {
      map['sale_type'] = Variable<String>(saleType.value);
    }
    if (discountBasisPoints.present) {
      map['discount_basis_points'] = Variable<int>(discountBasisPoints.value);
    }
    if (totalCents.present) {
      map['total_cents'] = Variable<int>(totalCents.value);
    }
    if (appliedCents.present) {
      map['applied_cents'] = Variable<int>(appliedCents.value);
    }
    if (receivedCents.present) {
      map['received_cents'] = Variable<int>(receivedCents.value);
    }
    if (changeCents.present) {
      map['change_cents'] = Variable<int>(changeCents.value);
    }
    if (balanceCents.present) {
      map['balance_cents'] = Variable<int>(balanceCents.value);
    }
    if (paid.present) {
      map['paid'] = Variable<bool>(paid.value);
    }
    if (status.present) {
      map['status'] = Variable<String>(status.value);
    }
    if (occurredAt.present) {
      map['occurred_at'] = Variable<String>(occurredAt.value);
    }
    if (offlineLeaseId.present) {
      map['offline_lease_id'] = Variable<String>(offlineLeaseId.value);
    }
    if (catalogRevision.present) {
      map['catalog_revision'] = Variable<String>(catalogRevision.value);
    }
    if (requestHash.present) {
      map['request_hash'] = Variable<String>(requestHash.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('LocalSalesCompanion(')
          ..write('id: $id, ')
          ..write('operationId: $operationId, ')
          ..write('contextId: $contextId, ')
          ..write('sequence: $sequence, ')
          ..write('localFolio: $localFolio, ')
          ..write('serverFolio: $serverFolio, ')
          ..write('customerId: $customerId, ')
          ..write('customerName: $customerName, ')
          ..write('branchName: $branchName, ')
          ..write('operatorName: $operatorName, ')
          ..write('saleType: $saleType, ')
          ..write('discountBasisPoints: $discountBasisPoints, ')
          ..write('totalCents: $totalCents, ')
          ..write('appliedCents: $appliedCents, ')
          ..write('receivedCents: $receivedCents, ')
          ..write('changeCents: $changeCents, ')
          ..write('balanceCents: $balanceCents, ')
          ..write('paid: $paid, ')
          ..write('status: $status, ')
          ..write('occurredAt: $occurredAt, ')
          ..write('offlineLeaseId: $offlineLeaseId, ')
          ..write('catalogRevision: $catalogRevision, ')
          ..write('requestHash: $requestHash, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $LocalSaleItemsTable extends LocalSaleItems
    with TableInfo<$LocalSaleItemsTable, LocalSaleItem> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $LocalSaleItemsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<String> id = GeneratedColumn<String>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _saleIdMeta = const VerificationMeta('saleId');
  @override
  late final GeneratedColumn<String> saleId = GeneratedColumn<String>(
    'sale_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'REFERENCES local_sales (id)',
    ),
  );
  static const VerificationMeta _productIdMeta = const VerificationMeta(
    'productId',
  );
  @override
  late final GeneratedColumn<String> productId = GeneratedColumn<String>(
    'product_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _productNameMeta = const VerificationMeta(
    'productName',
  );
  @override
  late final GeneratedColumn<String> productName = GeneratedColumn<String>(
    'product_name',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _barcodeMeta = const VerificationMeta(
    'barcode',
  );
  @override
  late final GeneratedColumn<String> barcode = GeneratedColumn<String>(
    'barcode',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _quantityUnitsMeta = const VerificationMeta(
    'quantityUnits',
  );
  @override
  late final GeneratedColumn<int> quantityUnits = GeneratedColumn<int>(
    'quantity_units',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _originalPriceCentsMeta =
      const VerificationMeta('originalPriceCents');
  @override
  late final GeneratedColumn<int> originalPriceCents = GeneratedColumn<int>(
    'original_price_cents',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _unitPriceCentsMeta = const VerificationMeta(
    'unitPriceCents',
  );
  @override
  late final GeneratedColumn<int> unitPriceCents = GeneratedColumn<int>(
    'unit_price_cents',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _totalCentsMeta = const VerificationMeta(
    'totalCents',
  );
  @override
  late final GeneratedColumn<int> totalCents = GeneratedColumn<int>(
    'total_cents',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _ordinalMeta = const VerificationMeta(
    'ordinal',
  );
  @override
  late final GeneratedColumn<int> ordinal = GeneratedColumn<int>(
    'ordinal',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [
    id,
    saleId,
    productId,
    productName,
    barcode,
    quantityUnits,
    originalPriceCents,
    unitPriceCents,
    totalCents,
    ordinal,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'local_sale_items';
  @override
  VerificationContext validateIntegrity(
    Insertable<LocalSaleItem> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    } else if (isInserting) {
      context.missing(_idMeta);
    }
    if (data.containsKey('sale_id')) {
      context.handle(
        _saleIdMeta,
        saleId.isAcceptableOrUnknown(data['sale_id']!, _saleIdMeta),
      );
    } else if (isInserting) {
      context.missing(_saleIdMeta);
    }
    if (data.containsKey('product_id')) {
      context.handle(
        _productIdMeta,
        productId.isAcceptableOrUnknown(data['product_id']!, _productIdMeta),
      );
    } else if (isInserting) {
      context.missing(_productIdMeta);
    }
    if (data.containsKey('product_name')) {
      context.handle(
        _productNameMeta,
        productName.isAcceptableOrUnknown(
          data['product_name']!,
          _productNameMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_productNameMeta);
    }
    if (data.containsKey('barcode')) {
      context.handle(
        _barcodeMeta,
        barcode.isAcceptableOrUnknown(data['barcode']!, _barcodeMeta),
      );
    } else if (isInserting) {
      context.missing(_barcodeMeta);
    }
    if (data.containsKey('quantity_units')) {
      context.handle(
        _quantityUnitsMeta,
        quantityUnits.isAcceptableOrUnknown(
          data['quantity_units']!,
          _quantityUnitsMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_quantityUnitsMeta);
    }
    if (data.containsKey('original_price_cents')) {
      context.handle(
        _originalPriceCentsMeta,
        originalPriceCents.isAcceptableOrUnknown(
          data['original_price_cents']!,
          _originalPriceCentsMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_originalPriceCentsMeta);
    }
    if (data.containsKey('unit_price_cents')) {
      context.handle(
        _unitPriceCentsMeta,
        unitPriceCents.isAcceptableOrUnknown(
          data['unit_price_cents']!,
          _unitPriceCentsMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_unitPriceCentsMeta);
    }
    if (data.containsKey('total_cents')) {
      context.handle(
        _totalCentsMeta,
        totalCents.isAcceptableOrUnknown(data['total_cents']!, _totalCentsMeta),
      );
    } else if (isInserting) {
      context.missing(_totalCentsMeta);
    }
    if (data.containsKey('ordinal')) {
      context.handle(
        _ordinalMeta,
        ordinal.isAcceptableOrUnknown(data['ordinal']!, _ordinalMeta),
      );
    } else if (isInserting) {
      context.missing(_ordinalMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  LocalSaleItem map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return LocalSaleItem(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}id'],
      )!,
      saleId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}sale_id'],
      )!,
      productId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}product_id'],
      )!,
      productName: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}product_name'],
      )!,
      barcode: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}barcode'],
      )!,
      quantityUnits: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}quantity_units'],
      )!,
      originalPriceCents: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}original_price_cents'],
      )!,
      unitPriceCents: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}unit_price_cents'],
      )!,
      totalCents: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}total_cents'],
      )!,
      ordinal: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}ordinal'],
      )!,
    );
  }

  @override
  $LocalSaleItemsTable createAlias(String alias) {
    return $LocalSaleItemsTable(attachedDatabase, alias);
  }
}

class LocalSaleItem extends DataClass implements Insertable<LocalSaleItem> {
  final String id;
  final String saleId;
  final String productId;
  final String productName;
  final String barcode;
  final int quantityUnits;
  final int originalPriceCents;
  final int unitPriceCents;
  final int totalCents;
  final int ordinal;
  const LocalSaleItem({
    required this.id,
    required this.saleId,
    required this.productId,
    required this.productName,
    required this.barcode,
    required this.quantityUnits,
    required this.originalPriceCents,
    required this.unitPriceCents,
    required this.totalCents,
    required this.ordinal,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<String>(id);
    map['sale_id'] = Variable<String>(saleId);
    map['product_id'] = Variable<String>(productId);
    map['product_name'] = Variable<String>(productName);
    map['barcode'] = Variable<String>(barcode);
    map['quantity_units'] = Variable<int>(quantityUnits);
    map['original_price_cents'] = Variable<int>(originalPriceCents);
    map['unit_price_cents'] = Variable<int>(unitPriceCents);
    map['total_cents'] = Variable<int>(totalCents);
    map['ordinal'] = Variable<int>(ordinal);
    return map;
  }

  LocalSaleItemsCompanion toCompanion(bool nullToAbsent) {
    return LocalSaleItemsCompanion(
      id: Value(id),
      saleId: Value(saleId),
      productId: Value(productId),
      productName: Value(productName),
      barcode: Value(barcode),
      quantityUnits: Value(quantityUnits),
      originalPriceCents: Value(originalPriceCents),
      unitPriceCents: Value(unitPriceCents),
      totalCents: Value(totalCents),
      ordinal: Value(ordinal),
    );
  }

  factory LocalSaleItem.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return LocalSaleItem(
      id: serializer.fromJson<String>(json['id']),
      saleId: serializer.fromJson<String>(json['saleId']),
      productId: serializer.fromJson<String>(json['productId']),
      productName: serializer.fromJson<String>(json['productName']),
      barcode: serializer.fromJson<String>(json['barcode']),
      quantityUnits: serializer.fromJson<int>(json['quantityUnits']),
      originalPriceCents: serializer.fromJson<int>(json['originalPriceCents']),
      unitPriceCents: serializer.fromJson<int>(json['unitPriceCents']),
      totalCents: serializer.fromJson<int>(json['totalCents']),
      ordinal: serializer.fromJson<int>(json['ordinal']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<String>(id),
      'saleId': serializer.toJson<String>(saleId),
      'productId': serializer.toJson<String>(productId),
      'productName': serializer.toJson<String>(productName),
      'barcode': serializer.toJson<String>(barcode),
      'quantityUnits': serializer.toJson<int>(quantityUnits),
      'originalPriceCents': serializer.toJson<int>(originalPriceCents),
      'unitPriceCents': serializer.toJson<int>(unitPriceCents),
      'totalCents': serializer.toJson<int>(totalCents),
      'ordinal': serializer.toJson<int>(ordinal),
    };
  }

  LocalSaleItem copyWith({
    String? id,
    String? saleId,
    String? productId,
    String? productName,
    String? barcode,
    int? quantityUnits,
    int? originalPriceCents,
    int? unitPriceCents,
    int? totalCents,
    int? ordinal,
  }) => LocalSaleItem(
    id: id ?? this.id,
    saleId: saleId ?? this.saleId,
    productId: productId ?? this.productId,
    productName: productName ?? this.productName,
    barcode: barcode ?? this.barcode,
    quantityUnits: quantityUnits ?? this.quantityUnits,
    originalPriceCents: originalPriceCents ?? this.originalPriceCents,
    unitPriceCents: unitPriceCents ?? this.unitPriceCents,
    totalCents: totalCents ?? this.totalCents,
    ordinal: ordinal ?? this.ordinal,
  );
  LocalSaleItem copyWithCompanion(LocalSaleItemsCompanion data) {
    return LocalSaleItem(
      id: data.id.present ? data.id.value : this.id,
      saleId: data.saleId.present ? data.saleId.value : this.saleId,
      productId: data.productId.present ? data.productId.value : this.productId,
      productName: data.productName.present
          ? data.productName.value
          : this.productName,
      barcode: data.barcode.present ? data.barcode.value : this.barcode,
      quantityUnits: data.quantityUnits.present
          ? data.quantityUnits.value
          : this.quantityUnits,
      originalPriceCents: data.originalPriceCents.present
          ? data.originalPriceCents.value
          : this.originalPriceCents,
      unitPriceCents: data.unitPriceCents.present
          ? data.unitPriceCents.value
          : this.unitPriceCents,
      totalCents: data.totalCents.present
          ? data.totalCents.value
          : this.totalCents,
      ordinal: data.ordinal.present ? data.ordinal.value : this.ordinal,
    );
  }

  @override
  String toString() {
    return (StringBuffer('LocalSaleItem(')
          ..write('id: $id, ')
          ..write('saleId: $saleId, ')
          ..write('productId: $productId, ')
          ..write('productName: $productName, ')
          ..write('barcode: $barcode, ')
          ..write('quantityUnits: $quantityUnits, ')
          ..write('originalPriceCents: $originalPriceCents, ')
          ..write('unitPriceCents: $unitPriceCents, ')
          ..write('totalCents: $totalCents, ')
          ..write('ordinal: $ordinal')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    id,
    saleId,
    productId,
    productName,
    barcode,
    quantityUnits,
    originalPriceCents,
    unitPriceCents,
    totalCents,
    ordinal,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is LocalSaleItem &&
          other.id == this.id &&
          other.saleId == this.saleId &&
          other.productId == this.productId &&
          other.productName == this.productName &&
          other.barcode == this.barcode &&
          other.quantityUnits == this.quantityUnits &&
          other.originalPriceCents == this.originalPriceCents &&
          other.unitPriceCents == this.unitPriceCents &&
          other.totalCents == this.totalCents &&
          other.ordinal == this.ordinal);
}

class LocalSaleItemsCompanion extends UpdateCompanion<LocalSaleItem> {
  final Value<String> id;
  final Value<String> saleId;
  final Value<String> productId;
  final Value<String> productName;
  final Value<String> barcode;
  final Value<int> quantityUnits;
  final Value<int> originalPriceCents;
  final Value<int> unitPriceCents;
  final Value<int> totalCents;
  final Value<int> ordinal;
  final Value<int> rowid;
  const LocalSaleItemsCompanion({
    this.id = const Value.absent(),
    this.saleId = const Value.absent(),
    this.productId = const Value.absent(),
    this.productName = const Value.absent(),
    this.barcode = const Value.absent(),
    this.quantityUnits = const Value.absent(),
    this.originalPriceCents = const Value.absent(),
    this.unitPriceCents = const Value.absent(),
    this.totalCents = const Value.absent(),
    this.ordinal = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  LocalSaleItemsCompanion.insert({
    required String id,
    required String saleId,
    required String productId,
    required String productName,
    required String barcode,
    required int quantityUnits,
    required int originalPriceCents,
    required int unitPriceCents,
    required int totalCents,
    required int ordinal,
    this.rowid = const Value.absent(),
  }) : id = Value(id),
       saleId = Value(saleId),
       productId = Value(productId),
       productName = Value(productName),
       barcode = Value(barcode),
       quantityUnits = Value(quantityUnits),
       originalPriceCents = Value(originalPriceCents),
       unitPriceCents = Value(unitPriceCents),
       totalCents = Value(totalCents),
       ordinal = Value(ordinal);
  static Insertable<LocalSaleItem> custom({
    Expression<String>? id,
    Expression<String>? saleId,
    Expression<String>? productId,
    Expression<String>? productName,
    Expression<String>? barcode,
    Expression<int>? quantityUnits,
    Expression<int>? originalPriceCents,
    Expression<int>? unitPriceCents,
    Expression<int>? totalCents,
    Expression<int>? ordinal,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (saleId != null) 'sale_id': saleId,
      if (productId != null) 'product_id': productId,
      if (productName != null) 'product_name': productName,
      if (barcode != null) 'barcode': barcode,
      if (quantityUnits != null) 'quantity_units': quantityUnits,
      if (originalPriceCents != null)
        'original_price_cents': originalPriceCents,
      if (unitPriceCents != null) 'unit_price_cents': unitPriceCents,
      if (totalCents != null) 'total_cents': totalCents,
      if (ordinal != null) 'ordinal': ordinal,
      if (rowid != null) 'rowid': rowid,
    });
  }

  LocalSaleItemsCompanion copyWith({
    Value<String>? id,
    Value<String>? saleId,
    Value<String>? productId,
    Value<String>? productName,
    Value<String>? barcode,
    Value<int>? quantityUnits,
    Value<int>? originalPriceCents,
    Value<int>? unitPriceCents,
    Value<int>? totalCents,
    Value<int>? ordinal,
    Value<int>? rowid,
  }) {
    return LocalSaleItemsCompanion(
      id: id ?? this.id,
      saleId: saleId ?? this.saleId,
      productId: productId ?? this.productId,
      productName: productName ?? this.productName,
      barcode: barcode ?? this.barcode,
      quantityUnits: quantityUnits ?? this.quantityUnits,
      originalPriceCents: originalPriceCents ?? this.originalPriceCents,
      unitPriceCents: unitPriceCents ?? this.unitPriceCents,
      totalCents: totalCents ?? this.totalCents,
      ordinal: ordinal ?? this.ordinal,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<String>(id.value);
    }
    if (saleId.present) {
      map['sale_id'] = Variable<String>(saleId.value);
    }
    if (productId.present) {
      map['product_id'] = Variable<String>(productId.value);
    }
    if (productName.present) {
      map['product_name'] = Variable<String>(productName.value);
    }
    if (barcode.present) {
      map['barcode'] = Variable<String>(barcode.value);
    }
    if (quantityUnits.present) {
      map['quantity_units'] = Variable<int>(quantityUnits.value);
    }
    if (originalPriceCents.present) {
      map['original_price_cents'] = Variable<int>(originalPriceCents.value);
    }
    if (unitPriceCents.present) {
      map['unit_price_cents'] = Variable<int>(unitPriceCents.value);
    }
    if (totalCents.present) {
      map['total_cents'] = Variable<int>(totalCents.value);
    }
    if (ordinal.present) {
      map['ordinal'] = Variable<int>(ordinal.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('LocalSaleItemsCompanion(')
          ..write('id: $id, ')
          ..write('saleId: $saleId, ')
          ..write('productId: $productId, ')
          ..write('productName: $productName, ')
          ..write('barcode: $barcode, ')
          ..write('quantityUnits: $quantityUnits, ')
          ..write('originalPriceCents: $originalPriceCents, ')
          ..write('unitPriceCents: $unitPriceCents, ')
          ..write('totalCents: $totalCents, ')
          ..write('ordinal: $ordinal, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $LocalPaymentsTable extends LocalPayments
    with TableInfo<$LocalPaymentsTable, LocalPayment> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $LocalPaymentsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<String> id = GeneratedColumn<String>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _saleIdMeta = const VerificationMeta('saleId');
  @override
  late final GeneratedColumn<String> saleId = GeneratedColumn<String>(
    'sale_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'UNIQUE REFERENCES local_sales (id)',
    ),
  );
  static const VerificationMeta _methodMeta = const VerificationMeta('method');
  @override
  late final GeneratedColumn<String> method = GeneratedColumn<String>(
    'method',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
    defaultValue: const Constant('cash'),
  );
  static const VerificationMeta _appliedCentsMeta = const VerificationMeta(
    'appliedCents',
  );
  @override
  late final GeneratedColumn<int> appliedCents = GeneratedColumn<int>(
    'applied_cents',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _receivedCentsMeta = const VerificationMeta(
    'receivedCents',
  );
  @override
  late final GeneratedColumn<int> receivedCents = GeneratedColumn<int>(
    'received_cents',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _changeCentsMeta = const VerificationMeta(
    'changeCents',
  );
  @override
  late final GeneratedColumn<int> changeCents = GeneratedColumn<int>(
    'change_cents',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [
    id,
    saleId,
    method,
    appliedCents,
    receivedCents,
    changeCents,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'local_payments';
  @override
  VerificationContext validateIntegrity(
    Insertable<LocalPayment> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    } else if (isInserting) {
      context.missing(_idMeta);
    }
    if (data.containsKey('sale_id')) {
      context.handle(
        _saleIdMeta,
        saleId.isAcceptableOrUnknown(data['sale_id']!, _saleIdMeta),
      );
    } else if (isInserting) {
      context.missing(_saleIdMeta);
    }
    if (data.containsKey('method')) {
      context.handle(
        _methodMeta,
        method.isAcceptableOrUnknown(data['method']!, _methodMeta),
      );
    }
    if (data.containsKey('applied_cents')) {
      context.handle(
        _appliedCentsMeta,
        appliedCents.isAcceptableOrUnknown(
          data['applied_cents']!,
          _appliedCentsMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_appliedCentsMeta);
    }
    if (data.containsKey('received_cents')) {
      context.handle(
        _receivedCentsMeta,
        receivedCents.isAcceptableOrUnknown(
          data['received_cents']!,
          _receivedCentsMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_receivedCentsMeta);
    }
    if (data.containsKey('change_cents')) {
      context.handle(
        _changeCentsMeta,
        changeCents.isAcceptableOrUnknown(
          data['change_cents']!,
          _changeCentsMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_changeCentsMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  LocalPayment map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return LocalPayment(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}id'],
      )!,
      saleId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}sale_id'],
      )!,
      method: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}method'],
      )!,
      appliedCents: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}applied_cents'],
      )!,
      receivedCents: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}received_cents'],
      )!,
      changeCents: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}change_cents'],
      )!,
    );
  }

  @override
  $LocalPaymentsTable createAlias(String alias) {
    return $LocalPaymentsTable(attachedDatabase, alias);
  }
}

class LocalPayment extends DataClass implements Insertable<LocalPayment> {
  final String id;
  final String saleId;
  final String method;
  final int appliedCents;
  final int receivedCents;
  final int changeCents;
  const LocalPayment({
    required this.id,
    required this.saleId,
    required this.method,
    required this.appliedCents,
    required this.receivedCents,
    required this.changeCents,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<String>(id);
    map['sale_id'] = Variable<String>(saleId);
    map['method'] = Variable<String>(method);
    map['applied_cents'] = Variable<int>(appliedCents);
    map['received_cents'] = Variable<int>(receivedCents);
    map['change_cents'] = Variable<int>(changeCents);
    return map;
  }

  LocalPaymentsCompanion toCompanion(bool nullToAbsent) {
    return LocalPaymentsCompanion(
      id: Value(id),
      saleId: Value(saleId),
      method: Value(method),
      appliedCents: Value(appliedCents),
      receivedCents: Value(receivedCents),
      changeCents: Value(changeCents),
    );
  }

  factory LocalPayment.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return LocalPayment(
      id: serializer.fromJson<String>(json['id']),
      saleId: serializer.fromJson<String>(json['saleId']),
      method: serializer.fromJson<String>(json['method']),
      appliedCents: serializer.fromJson<int>(json['appliedCents']),
      receivedCents: serializer.fromJson<int>(json['receivedCents']),
      changeCents: serializer.fromJson<int>(json['changeCents']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<String>(id),
      'saleId': serializer.toJson<String>(saleId),
      'method': serializer.toJson<String>(method),
      'appliedCents': serializer.toJson<int>(appliedCents),
      'receivedCents': serializer.toJson<int>(receivedCents),
      'changeCents': serializer.toJson<int>(changeCents),
    };
  }

  LocalPayment copyWith({
    String? id,
    String? saleId,
    String? method,
    int? appliedCents,
    int? receivedCents,
    int? changeCents,
  }) => LocalPayment(
    id: id ?? this.id,
    saleId: saleId ?? this.saleId,
    method: method ?? this.method,
    appliedCents: appliedCents ?? this.appliedCents,
    receivedCents: receivedCents ?? this.receivedCents,
    changeCents: changeCents ?? this.changeCents,
  );
  LocalPayment copyWithCompanion(LocalPaymentsCompanion data) {
    return LocalPayment(
      id: data.id.present ? data.id.value : this.id,
      saleId: data.saleId.present ? data.saleId.value : this.saleId,
      method: data.method.present ? data.method.value : this.method,
      appliedCents: data.appliedCents.present
          ? data.appliedCents.value
          : this.appliedCents,
      receivedCents: data.receivedCents.present
          ? data.receivedCents.value
          : this.receivedCents,
      changeCents: data.changeCents.present
          ? data.changeCents.value
          : this.changeCents,
    );
  }

  @override
  String toString() {
    return (StringBuffer('LocalPayment(')
          ..write('id: $id, ')
          ..write('saleId: $saleId, ')
          ..write('method: $method, ')
          ..write('appliedCents: $appliedCents, ')
          ..write('receivedCents: $receivedCents, ')
          ..write('changeCents: $changeCents')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode =>
      Object.hash(id, saleId, method, appliedCents, receivedCents, changeCents);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is LocalPayment &&
          other.id == this.id &&
          other.saleId == this.saleId &&
          other.method == this.method &&
          other.appliedCents == this.appliedCents &&
          other.receivedCents == this.receivedCents &&
          other.changeCents == this.changeCents);
}

class LocalPaymentsCompanion extends UpdateCompanion<LocalPayment> {
  final Value<String> id;
  final Value<String> saleId;
  final Value<String> method;
  final Value<int> appliedCents;
  final Value<int> receivedCents;
  final Value<int> changeCents;
  final Value<int> rowid;
  const LocalPaymentsCompanion({
    this.id = const Value.absent(),
    this.saleId = const Value.absent(),
    this.method = const Value.absent(),
    this.appliedCents = const Value.absent(),
    this.receivedCents = const Value.absent(),
    this.changeCents = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  LocalPaymentsCompanion.insert({
    required String id,
    required String saleId,
    this.method = const Value.absent(),
    required int appliedCents,
    required int receivedCents,
    required int changeCents,
    this.rowid = const Value.absent(),
  }) : id = Value(id),
       saleId = Value(saleId),
       appliedCents = Value(appliedCents),
       receivedCents = Value(receivedCents),
       changeCents = Value(changeCents);
  static Insertable<LocalPayment> custom({
    Expression<String>? id,
    Expression<String>? saleId,
    Expression<String>? method,
    Expression<int>? appliedCents,
    Expression<int>? receivedCents,
    Expression<int>? changeCents,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (saleId != null) 'sale_id': saleId,
      if (method != null) 'method': method,
      if (appliedCents != null) 'applied_cents': appliedCents,
      if (receivedCents != null) 'received_cents': receivedCents,
      if (changeCents != null) 'change_cents': changeCents,
      if (rowid != null) 'rowid': rowid,
    });
  }

  LocalPaymentsCompanion copyWith({
    Value<String>? id,
    Value<String>? saleId,
    Value<String>? method,
    Value<int>? appliedCents,
    Value<int>? receivedCents,
    Value<int>? changeCents,
    Value<int>? rowid,
  }) {
    return LocalPaymentsCompanion(
      id: id ?? this.id,
      saleId: saleId ?? this.saleId,
      method: method ?? this.method,
      appliedCents: appliedCents ?? this.appliedCents,
      receivedCents: receivedCents ?? this.receivedCents,
      changeCents: changeCents ?? this.changeCents,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<String>(id.value);
    }
    if (saleId.present) {
      map['sale_id'] = Variable<String>(saleId.value);
    }
    if (method.present) {
      map['method'] = Variable<String>(method.value);
    }
    if (appliedCents.present) {
      map['applied_cents'] = Variable<int>(appliedCents.value);
    }
    if (receivedCents.present) {
      map['received_cents'] = Variable<int>(receivedCents.value);
    }
    if (changeCents.present) {
      map['change_cents'] = Variable<int>(changeCents.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('LocalPaymentsCompanion(')
          ..write('id: $id, ')
          ..write('saleId: $saleId, ')
          ..write('method: $method, ')
          ..write('appliedCents: $appliedCents, ')
          ..write('receivedCents: $receivedCents, ')
          ..write('changeCents: $changeCents, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $OutboxTable extends Outbox with TableInfo<$OutboxTable, OutboxData> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $OutboxTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _operationIdMeta = const VerificationMeta(
    'operationId',
  );
  @override
  late final GeneratedColumn<String> operationId = GeneratedColumn<String>(
    'operation_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _saleIdMeta = const VerificationMeta('saleId');
  @override
  late final GeneratedColumn<String> saleId = GeneratedColumn<String>(
    'sale_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'UNIQUE REFERENCES local_sales (id)',
    ),
  );
  static const VerificationMeta _contextIdMeta = const VerificationMeta(
    'contextId',
  );
  @override
  late final GeneratedColumn<String> contextId = GeneratedColumn<String>(
    'context_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _sequenceMeta = const VerificationMeta(
    'sequence',
  );
  @override
  late final GeneratedColumn<int> sequence = GeneratedColumn<int>(
    'sequence',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
    defaultConstraints: GeneratedColumn.constraintIsAlways('UNIQUE'),
  );
  static const VerificationMeta _payloadMeta = const VerificationMeta(
    'payload',
  );
  @override
  late final GeneratedColumn<String> payload = GeneratedColumn<String>(
    'payload',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _payloadHashMeta = const VerificationMeta(
    'payloadHash',
  );
  @override
  late final GeneratedColumn<String> payloadHash = GeneratedColumn<String>(
    'payload_hash',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _statusMeta = const VerificationMeta('status');
  @override
  late final GeneratedColumn<String> status = GeneratedColumn<String>(
    'status',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
    defaultValue: const Constant('pending'),
  );
  static const VerificationMeta _attemptsMeta = const VerificationMeta(
    'attempts',
  );
  @override
  late final GeneratedColumn<int> attempts = GeneratedColumn<int>(
    'attempts',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
    defaultValue: const Constant(0),
  );
  static const VerificationMeta _nextAttemptAtMeta = const VerificationMeta(
    'nextAttemptAt',
  );
  @override
  late final GeneratedColumn<String> nextAttemptAt = GeneratedColumn<String>(
    'next_attempt_at',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _workerLeaseMeta = const VerificationMeta(
    'workerLease',
  );
  @override
  late final GeneratedColumn<String> workerLease = GeneratedColumn<String>(
    'worker_lease',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _serverResultMeta = const VerificationMeta(
    'serverResult',
  );
  @override
  late final GeneratedColumn<String> serverResult = GeneratedColumn<String>(
    'server_result',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _errorCodeMeta = const VerificationMeta(
    'errorCode',
  );
  @override
  late final GeneratedColumn<String> errorCode = GeneratedColumn<String>(
    'error_code',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _errorMeta = const VerificationMeta('error');
  @override
  late final GeneratedColumn<String> error = GeneratedColumn<String>(
    'error',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  @override
  List<GeneratedColumn> get $columns => [
    operationId,
    saleId,
    contextId,
    sequence,
    payload,
    payloadHash,
    status,
    attempts,
    nextAttemptAt,
    workerLease,
    serverResult,
    errorCode,
    error,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'outbox';
  @override
  VerificationContext validateIntegrity(
    Insertable<OutboxData> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('operation_id')) {
      context.handle(
        _operationIdMeta,
        operationId.isAcceptableOrUnknown(
          data['operation_id']!,
          _operationIdMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_operationIdMeta);
    }
    if (data.containsKey('sale_id')) {
      context.handle(
        _saleIdMeta,
        saleId.isAcceptableOrUnknown(data['sale_id']!, _saleIdMeta),
      );
    } else if (isInserting) {
      context.missing(_saleIdMeta);
    }
    if (data.containsKey('context_id')) {
      context.handle(
        _contextIdMeta,
        contextId.isAcceptableOrUnknown(data['context_id']!, _contextIdMeta),
      );
    } else if (isInserting) {
      context.missing(_contextIdMeta);
    }
    if (data.containsKey('sequence')) {
      context.handle(
        _sequenceMeta,
        sequence.isAcceptableOrUnknown(data['sequence']!, _sequenceMeta),
      );
    } else if (isInserting) {
      context.missing(_sequenceMeta);
    }
    if (data.containsKey('payload')) {
      context.handle(
        _payloadMeta,
        payload.isAcceptableOrUnknown(data['payload']!, _payloadMeta),
      );
    } else if (isInserting) {
      context.missing(_payloadMeta);
    }
    if (data.containsKey('payload_hash')) {
      context.handle(
        _payloadHashMeta,
        payloadHash.isAcceptableOrUnknown(
          data['payload_hash']!,
          _payloadHashMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_payloadHashMeta);
    }
    if (data.containsKey('status')) {
      context.handle(
        _statusMeta,
        status.isAcceptableOrUnknown(data['status']!, _statusMeta),
      );
    }
    if (data.containsKey('attempts')) {
      context.handle(
        _attemptsMeta,
        attempts.isAcceptableOrUnknown(data['attempts']!, _attemptsMeta),
      );
    }
    if (data.containsKey('next_attempt_at')) {
      context.handle(
        _nextAttemptAtMeta,
        nextAttemptAt.isAcceptableOrUnknown(
          data['next_attempt_at']!,
          _nextAttemptAtMeta,
        ),
      );
    }
    if (data.containsKey('worker_lease')) {
      context.handle(
        _workerLeaseMeta,
        workerLease.isAcceptableOrUnknown(
          data['worker_lease']!,
          _workerLeaseMeta,
        ),
      );
    }
    if (data.containsKey('server_result')) {
      context.handle(
        _serverResultMeta,
        serverResult.isAcceptableOrUnknown(
          data['server_result']!,
          _serverResultMeta,
        ),
      );
    }
    if (data.containsKey('error_code')) {
      context.handle(
        _errorCodeMeta,
        errorCode.isAcceptableOrUnknown(data['error_code']!, _errorCodeMeta),
      );
    }
    if (data.containsKey('error')) {
      context.handle(
        _errorMeta,
        error.isAcceptableOrUnknown(data['error']!, _errorMeta),
      );
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {operationId};
  @override
  OutboxData map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return OutboxData(
      operationId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}operation_id'],
      )!,
      saleId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}sale_id'],
      )!,
      contextId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}context_id'],
      )!,
      sequence: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}sequence'],
      )!,
      payload: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}payload'],
      )!,
      payloadHash: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}payload_hash'],
      )!,
      status: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}status'],
      )!,
      attempts: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}attempts'],
      )!,
      nextAttemptAt: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}next_attempt_at'],
      ),
      workerLease: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}worker_lease'],
      ),
      serverResult: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}server_result'],
      ),
      errorCode: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}error_code'],
      ),
      error: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}error'],
      ),
    );
  }

  @override
  $OutboxTable createAlias(String alias) {
    return $OutboxTable(attachedDatabase, alias);
  }
}

class OutboxData extends DataClass implements Insertable<OutboxData> {
  final String operationId;
  final String saleId;
  final String contextId;
  final int sequence;
  final String payload;
  final String payloadHash;
  final String status;
  final int attempts;
  final String? nextAttemptAt;
  final String? workerLease;
  final String? serverResult;
  final String? errorCode;
  final String? error;
  const OutboxData({
    required this.operationId,
    required this.saleId,
    required this.contextId,
    required this.sequence,
    required this.payload,
    required this.payloadHash,
    required this.status,
    required this.attempts,
    this.nextAttemptAt,
    this.workerLease,
    this.serverResult,
    this.errorCode,
    this.error,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['operation_id'] = Variable<String>(operationId);
    map['sale_id'] = Variable<String>(saleId);
    map['context_id'] = Variable<String>(contextId);
    map['sequence'] = Variable<int>(sequence);
    map['payload'] = Variable<String>(payload);
    map['payload_hash'] = Variable<String>(payloadHash);
    map['status'] = Variable<String>(status);
    map['attempts'] = Variable<int>(attempts);
    if (!nullToAbsent || nextAttemptAt != null) {
      map['next_attempt_at'] = Variable<String>(nextAttemptAt);
    }
    if (!nullToAbsent || workerLease != null) {
      map['worker_lease'] = Variable<String>(workerLease);
    }
    if (!nullToAbsent || serverResult != null) {
      map['server_result'] = Variable<String>(serverResult);
    }
    if (!nullToAbsent || errorCode != null) {
      map['error_code'] = Variable<String>(errorCode);
    }
    if (!nullToAbsent || error != null) {
      map['error'] = Variable<String>(error);
    }
    return map;
  }

  OutboxCompanion toCompanion(bool nullToAbsent) {
    return OutboxCompanion(
      operationId: Value(operationId),
      saleId: Value(saleId),
      contextId: Value(contextId),
      sequence: Value(sequence),
      payload: Value(payload),
      payloadHash: Value(payloadHash),
      status: Value(status),
      attempts: Value(attempts),
      nextAttemptAt: nextAttemptAt == null && nullToAbsent
          ? const Value.absent()
          : Value(nextAttemptAt),
      workerLease: workerLease == null && nullToAbsent
          ? const Value.absent()
          : Value(workerLease),
      serverResult: serverResult == null && nullToAbsent
          ? const Value.absent()
          : Value(serverResult),
      errorCode: errorCode == null && nullToAbsent
          ? const Value.absent()
          : Value(errorCode),
      error: error == null && nullToAbsent
          ? const Value.absent()
          : Value(error),
    );
  }

  factory OutboxData.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return OutboxData(
      operationId: serializer.fromJson<String>(json['operationId']),
      saleId: serializer.fromJson<String>(json['saleId']),
      contextId: serializer.fromJson<String>(json['contextId']),
      sequence: serializer.fromJson<int>(json['sequence']),
      payload: serializer.fromJson<String>(json['payload']),
      payloadHash: serializer.fromJson<String>(json['payloadHash']),
      status: serializer.fromJson<String>(json['status']),
      attempts: serializer.fromJson<int>(json['attempts']),
      nextAttemptAt: serializer.fromJson<String?>(json['nextAttemptAt']),
      workerLease: serializer.fromJson<String?>(json['workerLease']),
      serverResult: serializer.fromJson<String?>(json['serverResult']),
      errorCode: serializer.fromJson<String?>(json['errorCode']),
      error: serializer.fromJson<String?>(json['error']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'operationId': serializer.toJson<String>(operationId),
      'saleId': serializer.toJson<String>(saleId),
      'contextId': serializer.toJson<String>(contextId),
      'sequence': serializer.toJson<int>(sequence),
      'payload': serializer.toJson<String>(payload),
      'payloadHash': serializer.toJson<String>(payloadHash),
      'status': serializer.toJson<String>(status),
      'attempts': serializer.toJson<int>(attempts),
      'nextAttemptAt': serializer.toJson<String?>(nextAttemptAt),
      'workerLease': serializer.toJson<String?>(workerLease),
      'serverResult': serializer.toJson<String?>(serverResult),
      'errorCode': serializer.toJson<String?>(errorCode),
      'error': serializer.toJson<String?>(error),
    };
  }

  OutboxData copyWith({
    String? operationId,
    String? saleId,
    String? contextId,
    int? sequence,
    String? payload,
    String? payloadHash,
    String? status,
    int? attempts,
    Value<String?> nextAttemptAt = const Value.absent(),
    Value<String?> workerLease = const Value.absent(),
    Value<String?> serverResult = const Value.absent(),
    Value<String?> errorCode = const Value.absent(),
    Value<String?> error = const Value.absent(),
  }) => OutboxData(
    operationId: operationId ?? this.operationId,
    saleId: saleId ?? this.saleId,
    contextId: contextId ?? this.contextId,
    sequence: sequence ?? this.sequence,
    payload: payload ?? this.payload,
    payloadHash: payloadHash ?? this.payloadHash,
    status: status ?? this.status,
    attempts: attempts ?? this.attempts,
    nextAttemptAt: nextAttemptAt.present
        ? nextAttemptAt.value
        : this.nextAttemptAt,
    workerLease: workerLease.present ? workerLease.value : this.workerLease,
    serverResult: serverResult.present ? serverResult.value : this.serverResult,
    errorCode: errorCode.present ? errorCode.value : this.errorCode,
    error: error.present ? error.value : this.error,
  );
  OutboxData copyWithCompanion(OutboxCompanion data) {
    return OutboxData(
      operationId: data.operationId.present
          ? data.operationId.value
          : this.operationId,
      saleId: data.saleId.present ? data.saleId.value : this.saleId,
      contextId: data.contextId.present ? data.contextId.value : this.contextId,
      sequence: data.sequence.present ? data.sequence.value : this.sequence,
      payload: data.payload.present ? data.payload.value : this.payload,
      payloadHash: data.payloadHash.present
          ? data.payloadHash.value
          : this.payloadHash,
      status: data.status.present ? data.status.value : this.status,
      attempts: data.attempts.present ? data.attempts.value : this.attempts,
      nextAttemptAt: data.nextAttemptAt.present
          ? data.nextAttemptAt.value
          : this.nextAttemptAt,
      workerLease: data.workerLease.present
          ? data.workerLease.value
          : this.workerLease,
      serverResult: data.serverResult.present
          ? data.serverResult.value
          : this.serverResult,
      errorCode: data.errorCode.present ? data.errorCode.value : this.errorCode,
      error: data.error.present ? data.error.value : this.error,
    );
  }

  @override
  String toString() {
    return (StringBuffer('OutboxData(')
          ..write('operationId: $operationId, ')
          ..write('saleId: $saleId, ')
          ..write('contextId: $contextId, ')
          ..write('sequence: $sequence, ')
          ..write('payload: $payload, ')
          ..write('payloadHash: $payloadHash, ')
          ..write('status: $status, ')
          ..write('attempts: $attempts, ')
          ..write('nextAttemptAt: $nextAttemptAt, ')
          ..write('workerLease: $workerLease, ')
          ..write('serverResult: $serverResult, ')
          ..write('errorCode: $errorCode, ')
          ..write('error: $error')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    operationId,
    saleId,
    contextId,
    sequence,
    payload,
    payloadHash,
    status,
    attempts,
    nextAttemptAt,
    workerLease,
    serverResult,
    errorCode,
    error,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is OutboxData &&
          other.operationId == this.operationId &&
          other.saleId == this.saleId &&
          other.contextId == this.contextId &&
          other.sequence == this.sequence &&
          other.payload == this.payload &&
          other.payloadHash == this.payloadHash &&
          other.status == this.status &&
          other.attempts == this.attempts &&
          other.nextAttemptAt == this.nextAttemptAt &&
          other.workerLease == this.workerLease &&
          other.serverResult == this.serverResult &&
          other.errorCode == this.errorCode &&
          other.error == this.error);
}

class OutboxCompanion extends UpdateCompanion<OutboxData> {
  final Value<String> operationId;
  final Value<String> saleId;
  final Value<String> contextId;
  final Value<int> sequence;
  final Value<String> payload;
  final Value<String> payloadHash;
  final Value<String> status;
  final Value<int> attempts;
  final Value<String?> nextAttemptAt;
  final Value<String?> workerLease;
  final Value<String?> serverResult;
  final Value<String?> errorCode;
  final Value<String?> error;
  final Value<int> rowid;
  const OutboxCompanion({
    this.operationId = const Value.absent(),
    this.saleId = const Value.absent(),
    this.contextId = const Value.absent(),
    this.sequence = const Value.absent(),
    this.payload = const Value.absent(),
    this.payloadHash = const Value.absent(),
    this.status = const Value.absent(),
    this.attempts = const Value.absent(),
    this.nextAttemptAt = const Value.absent(),
    this.workerLease = const Value.absent(),
    this.serverResult = const Value.absent(),
    this.errorCode = const Value.absent(),
    this.error = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  OutboxCompanion.insert({
    required String operationId,
    required String saleId,
    required String contextId,
    required int sequence,
    required String payload,
    required String payloadHash,
    this.status = const Value.absent(),
    this.attempts = const Value.absent(),
    this.nextAttemptAt = const Value.absent(),
    this.workerLease = const Value.absent(),
    this.serverResult = const Value.absent(),
    this.errorCode = const Value.absent(),
    this.error = const Value.absent(),
    this.rowid = const Value.absent(),
  }) : operationId = Value(operationId),
       saleId = Value(saleId),
       contextId = Value(contextId),
       sequence = Value(sequence),
       payload = Value(payload),
       payloadHash = Value(payloadHash);
  static Insertable<OutboxData> custom({
    Expression<String>? operationId,
    Expression<String>? saleId,
    Expression<String>? contextId,
    Expression<int>? sequence,
    Expression<String>? payload,
    Expression<String>? payloadHash,
    Expression<String>? status,
    Expression<int>? attempts,
    Expression<String>? nextAttemptAt,
    Expression<String>? workerLease,
    Expression<String>? serverResult,
    Expression<String>? errorCode,
    Expression<String>? error,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (operationId != null) 'operation_id': operationId,
      if (saleId != null) 'sale_id': saleId,
      if (contextId != null) 'context_id': contextId,
      if (sequence != null) 'sequence': sequence,
      if (payload != null) 'payload': payload,
      if (payloadHash != null) 'payload_hash': payloadHash,
      if (status != null) 'status': status,
      if (attempts != null) 'attempts': attempts,
      if (nextAttemptAt != null) 'next_attempt_at': nextAttemptAt,
      if (workerLease != null) 'worker_lease': workerLease,
      if (serverResult != null) 'server_result': serverResult,
      if (errorCode != null) 'error_code': errorCode,
      if (error != null) 'error': error,
      if (rowid != null) 'rowid': rowid,
    });
  }

  OutboxCompanion copyWith({
    Value<String>? operationId,
    Value<String>? saleId,
    Value<String>? contextId,
    Value<int>? sequence,
    Value<String>? payload,
    Value<String>? payloadHash,
    Value<String>? status,
    Value<int>? attempts,
    Value<String?>? nextAttemptAt,
    Value<String?>? workerLease,
    Value<String?>? serverResult,
    Value<String?>? errorCode,
    Value<String?>? error,
    Value<int>? rowid,
  }) {
    return OutboxCompanion(
      operationId: operationId ?? this.operationId,
      saleId: saleId ?? this.saleId,
      contextId: contextId ?? this.contextId,
      sequence: sequence ?? this.sequence,
      payload: payload ?? this.payload,
      payloadHash: payloadHash ?? this.payloadHash,
      status: status ?? this.status,
      attempts: attempts ?? this.attempts,
      nextAttemptAt: nextAttemptAt ?? this.nextAttemptAt,
      workerLease: workerLease ?? this.workerLease,
      serverResult: serverResult ?? this.serverResult,
      errorCode: errorCode ?? this.errorCode,
      error: error ?? this.error,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (operationId.present) {
      map['operation_id'] = Variable<String>(operationId.value);
    }
    if (saleId.present) {
      map['sale_id'] = Variable<String>(saleId.value);
    }
    if (contextId.present) {
      map['context_id'] = Variable<String>(contextId.value);
    }
    if (sequence.present) {
      map['sequence'] = Variable<int>(sequence.value);
    }
    if (payload.present) {
      map['payload'] = Variable<String>(payload.value);
    }
    if (payloadHash.present) {
      map['payload_hash'] = Variable<String>(payloadHash.value);
    }
    if (status.present) {
      map['status'] = Variable<String>(status.value);
    }
    if (attempts.present) {
      map['attempts'] = Variable<int>(attempts.value);
    }
    if (nextAttemptAt.present) {
      map['next_attempt_at'] = Variable<String>(nextAttemptAt.value);
    }
    if (workerLease.present) {
      map['worker_lease'] = Variable<String>(workerLease.value);
    }
    if (serverResult.present) {
      map['server_result'] = Variable<String>(serverResult.value);
    }
    if (errorCode.present) {
      map['error_code'] = Variable<String>(errorCode.value);
    }
    if (error.present) {
      map['error'] = Variable<String>(error.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('OutboxCompanion(')
          ..write('operationId: $operationId, ')
          ..write('saleId: $saleId, ')
          ..write('contextId: $contextId, ')
          ..write('sequence: $sequence, ')
          ..write('payload: $payload, ')
          ..write('payloadHash: $payloadHash, ')
          ..write('status: $status, ')
          ..write('attempts: $attempts, ')
          ..write('nextAttemptAt: $nextAttemptAt, ')
          ..write('workerLease: $workerLease, ')
          ..write('serverResult: $serverResult, ')
          ..write('errorCode: $errorCode, ')
          ..write('error: $error, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $DeviceSequenceTable extends DeviceSequence
    with TableInfo<$DeviceSequenceTable, DeviceSequenceData> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $DeviceSequenceTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _contextIdMeta = const VerificationMeta(
    'contextId',
  );
  @override
  late final GeneratedColumn<String> contextId = GeneratedColumn<String>(
    'context_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _nextSequenceMeta = const VerificationMeta(
    'nextSequence',
  );
  @override
  late final GeneratedColumn<int> nextSequence = GeneratedColumn<int>(
    'next_sequence',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
    defaultValue: const Constant(1),
  );
  @override
  List<GeneratedColumn> get $columns => [contextId, nextSequence];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'device_sequence';
  @override
  VerificationContext validateIntegrity(
    Insertable<DeviceSequenceData> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('context_id')) {
      context.handle(
        _contextIdMeta,
        contextId.isAcceptableOrUnknown(data['context_id']!, _contextIdMeta),
      );
    } else if (isInserting) {
      context.missing(_contextIdMeta);
    }
    if (data.containsKey('next_sequence')) {
      context.handle(
        _nextSequenceMeta,
        nextSequence.isAcceptableOrUnknown(
          data['next_sequence']!,
          _nextSequenceMeta,
        ),
      );
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {contextId};
  @override
  DeviceSequenceData map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return DeviceSequenceData(
      contextId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}context_id'],
      )!,
      nextSequence: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}next_sequence'],
      )!,
    );
  }

  @override
  $DeviceSequenceTable createAlias(String alias) {
    return $DeviceSequenceTable(attachedDatabase, alias);
  }
}

class DeviceSequenceData extends DataClass
    implements Insertable<DeviceSequenceData> {
  final String contextId;
  final int nextSequence;
  const DeviceSequenceData({
    required this.contextId,
    required this.nextSequence,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['context_id'] = Variable<String>(contextId);
    map['next_sequence'] = Variable<int>(nextSequence);
    return map;
  }

  DeviceSequenceCompanion toCompanion(bool nullToAbsent) {
    return DeviceSequenceCompanion(
      contextId: Value(contextId),
      nextSequence: Value(nextSequence),
    );
  }

  factory DeviceSequenceData.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return DeviceSequenceData(
      contextId: serializer.fromJson<String>(json['contextId']),
      nextSequence: serializer.fromJson<int>(json['nextSequence']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'contextId': serializer.toJson<String>(contextId),
      'nextSequence': serializer.toJson<int>(nextSequence),
    };
  }

  DeviceSequenceData copyWith({String? contextId, int? nextSequence}) =>
      DeviceSequenceData(
        contextId: contextId ?? this.contextId,
        nextSequence: nextSequence ?? this.nextSequence,
      );
  DeviceSequenceData copyWithCompanion(DeviceSequenceCompanion data) {
    return DeviceSequenceData(
      contextId: data.contextId.present ? data.contextId.value : this.contextId,
      nextSequence: data.nextSequence.present
          ? data.nextSequence.value
          : this.nextSequence,
    );
  }

  @override
  String toString() {
    return (StringBuffer('DeviceSequenceData(')
          ..write('contextId: $contextId, ')
          ..write('nextSequence: $nextSequence')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(contextId, nextSequence);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is DeviceSequenceData &&
          other.contextId == this.contextId &&
          other.nextSequence == this.nextSequence);
}

class DeviceSequenceCompanion extends UpdateCompanion<DeviceSequenceData> {
  final Value<String> contextId;
  final Value<int> nextSequence;
  final Value<int> rowid;
  const DeviceSequenceCompanion({
    this.contextId = const Value.absent(),
    this.nextSequence = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  DeviceSequenceCompanion.insert({
    required String contextId,
    this.nextSequence = const Value.absent(),
    this.rowid = const Value.absent(),
  }) : contextId = Value(contextId);
  static Insertable<DeviceSequenceData> custom({
    Expression<String>? contextId,
    Expression<int>? nextSequence,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (contextId != null) 'context_id': contextId,
      if (nextSequence != null) 'next_sequence': nextSequence,
      if (rowid != null) 'rowid': rowid,
    });
  }

  DeviceSequenceCompanion copyWith({
    Value<String>? contextId,
    Value<int>? nextSequence,
    Value<int>? rowid,
  }) {
    return DeviceSequenceCompanion(
      contextId: contextId ?? this.contextId,
      nextSequence: nextSequence ?? this.nextSequence,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (contextId.present) {
      map['context_id'] = Variable<String>(contextId.value);
    }
    if (nextSequence.present) {
      map['next_sequence'] = Variable<int>(nextSequence.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('DeviceSequenceCompanion(')
          ..write('contextId: $contextId, ')
          ..write('nextSequence: $nextSequence, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $ReceiptAttemptsTable extends ReceiptAttempts
    with TableInfo<$ReceiptAttemptsTable, ReceiptAttempt> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $ReceiptAttemptsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<String> id = GeneratedColumn<String>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _saleIdMeta = const VerificationMeta('saleId');
  @override
  late final GeneratedColumn<String> saleId = GeneratedColumn<String>(
    'sale_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'REFERENCES local_sales (id)',
    ),
  );
  static const VerificationMeta _attemptedAtMeta = const VerificationMeta(
    'attemptedAt',
  );
  @override
  late final GeneratedColumn<String> attemptedAt = GeneratedColumn<String>(
    'attempted_at',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _statusMeta = const VerificationMeta('status');
  @override
  late final GeneratedColumn<String> status = GeneratedColumn<String>(
    'status',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _messageMeta = const VerificationMeta(
    'message',
  );
  @override
  late final GeneratedColumn<String> message = GeneratedColumn<String>(
    'message',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  @override
  List<GeneratedColumn> get $columns => [
    id,
    saleId,
    attemptedAt,
    status,
    message,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'receipt_attempts';
  @override
  VerificationContext validateIntegrity(
    Insertable<ReceiptAttempt> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    } else if (isInserting) {
      context.missing(_idMeta);
    }
    if (data.containsKey('sale_id')) {
      context.handle(
        _saleIdMeta,
        saleId.isAcceptableOrUnknown(data['sale_id']!, _saleIdMeta),
      );
    } else if (isInserting) {
      context.missing(_saleIdMeta);
    }
    if (data.containsKey('attempted_at')) {
      context.handle(
        _attemptedAtMeta,
        attemptedAt.isAcceptableOrUnknown(
          data['attempted_at']!,
          _attemptedAtMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_attemptedAtMeta);
    }
    if (data.containsKey('status')) {
      context.handle(
        _statusMeta,
        status.isAcceptableOrUnknown(data['status']!, _statusMeta),
      );
    } else if (isInserting) {
      context.missing(_statusMeta);
    }
    if (data.containsKey('message')) {
      context.handle(
        _messageMeta,
        message.isAcceptableOrUnknown(data['message']!, _messageMeta),
      );
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  ReceiptAttempt map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return ReceiptAttempt(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}id'],
      )!,
      saleId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}sale_id'],
      )!,
      attemptedAt: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}attempted_at'],
      )!,
      status: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}status'],
      )!,
      message: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}message'],
      ),
    );
  }

  @override
  $ReceiptAttemptsTable createAlias(String alias) {
    return $ReceiptAttemptsTable(attachedDatabase, alias);
  }
}

class ReceiptAttempt extends DataClass implements Insertable<ReceiptAttempt> {
  final String id;
  final String saleId;
  final String attemptedAt;
  final String status;
  final String? message;
  const ReceiptAttempt({
    required this.id,
    required this.saleId,
    required this.attemptedAt,
    required this.status,
    this.message,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<String>(id);
    map['sale_id'] = Variable<String>(saleId);
    map['attempted_at'] = Variable<String>(attemptedAt);
    map['status'] = Variable<String>(status);
    if (!nullToAbsent || message != null) {
      map['message'] = Variable<String>(message);
    }
    return map;
  }

  ReceiptAttemptsCompanion toCompanion(bool nullToAbsent) {
    return ReceiptAttemptsCompanion(
      id: Value(id),
      saleId: Value(saleId),
      attemptedAt: Value(attemptedAt),
      status: Value(status),
      message: message == null && nullToAbsent
          ? const Value.absent()
          : Value(message),
    );
  }

  factory ReceiptAttempt.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return ReceiptAttempt(
      id: serializer.fromJson<String>(json['id']),
      saleId: serializer.fromJson<String>(json['saleId']),
      attemptedAt: serializer.fromJson<String>(json['attemptedAt']),
      status: serializer.fromJson<String>(json['status']),
      message: serializer.fromJson<String?>(json['message']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<String>(id),
      'saleId': serializer.toJson<String>(saleId),
      'attemptedAt': serializer.toJson<String>(attemptedAt),
      'status': serializer.toJson<String>(status),
      'message': serializer.toJson<String?>(message),
    };
  }

  ReceiptAttempt copyWith({
    String? id,
    String? saleId,
    String? attemptedAt,
    String? status,
    Value<String?> message = const Value.absent(),
  }) => ReceiptAttempt(
    id: id ?? this.id,
    saleId: saleId ?? this.saleId,
    attemptedAt: attemptedAt ?? this.attemptedAt,
    status: status ?? this.status,
    message: message.present ? message.value : this.message,
  );
  ReceiptAttempt copyWithCompanion(ReceiptAttemptsCompanion data) {
    return ReceiptAttempt(
      id: data.id.present ? data.id.value : this.id,
      saleId: data.saleId.present ? data.saleId.value : this.saleId,
      attemptedAt: data.attemptedAt.present
          ? data.attemptedAt.value
          : this.attemptedAt,
      status: data.status.present ? data.status.value : this.status,
      message: data.message.present ? data.message.value : this.message,
    );
  }

  @override
  String toString() {
    return (StringBuffer('ReceiptAttempt(')
          ..write('id: $id, ')
          ..write('saleId: $saleId, ')
          ..write('attemptedAt: $attemptedAt, ')
          ..write('status: $status, ')
          ..write('message: $message')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(id, saleId, attemptedAt, status, message);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is ReceiptAttempt &&
          other.id == this.id &&
          other.saleId == this.saleId &&
          other.attemptedAt == this.attemptedAt &&
          other.status == this.status &&
          other.message == this.message);
}

class ReceiptAttemptsCompanion extends UpdateCompanion<ReceiptAttempt> {
  final Value<String> id;
  final Value<String> saleId;
  final Value<String> attemptedAt;
  final Value<String> status;
  final Value<String?> message;
  final Value<int> rowid;
  const ReceiptAttemptsCompanion({
    this.id = const Value.absent(),
    this.saleId = const Value.absent(),
    this.attemptedAt = const Value.absent(),
    this.status = const Value.absent(),
    this.message = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  ReceiptAttemptsCompanion.insert({
    required String id,
    required String saleId,
    required String attemptedAt,
    required String status,
    this.message = const Value.absent(),
    this.rowid = const Value.absent(),
  }) : id = Value(id),
       saleId = Value(saleId),
       attemptedAt = Value(attemptedAt),
       status = Value(status);
  static Insertable<ReceiptAttempt> custom({
    Expression<String>? id,
    Expression<String>? saleId,
    Expression<String>? attemptedAt,
    Expression<String>? status,
    Expression<String>? message,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (saleId != null) 'sale_id': saleId,
      if (attemptedAt != null) 'attempted_at': attemptedAt,
      if (status != null) 'status': status,
      if (message != null) 'message': message,
      if (rowid != null) 'rowid': rowid,
    });
  }

  ReceiptAttemptsCompanion copyWith({
    Value<String>? id,
    Value<String>? saleId,
    Value<String>? attemptedAt,
    Value<String>? status,
    Value<String?>? message,
    Value<int>? rowid,
  }) {
    return ReceiptAttemptsCompanion(
      id: id ?? this.id,
      saleId: saleId ?? this.saleId,
      attemptedAt: attemptedAt ?? this.attemptedAt,
      status: status ?? this.status,
      message: message ?? this.message,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<String>(id.value);
    }
    if (saleId.present) {
      map['sale_id'] = Variable<String>(saleId.value);
    }
    if (attemptedAt.present) {
      map['attempted_at'] = Variable<String>(attemptedAt.value);
    }
    if (status.present) {
      map['status'] = Variable<String>(status.value);
    }
    if (message.present) {
      map['message'] = Variable<String>(message.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('ReceiptAttemptsCompanion(')
          ..write('id: $id, ')
          ..write('saleId: $saleId, ')
          ..write('attemptedAt: $attemptedAt, ')
          ..write('status: $status, ')
          ..write('message: $message, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $SyncWorkersTable extends SyncWorkers
    with TableInfo<$SyncWorkersTable, SyncWorker> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $SyncWorkersTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _contextIdMeta = const VerificationMeta(
    'contextId',
  );
  @override
  late final GeneratedColumn<String> contextId = GeneratedColumn<String>(
    'context_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _ownerMeta = const VerificationMeta('owner');
  @override
  late final GeneratedColumn<String> owner = GeneratedColumn<String>(
    'owner',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _expiresAtMeta = const VerificationMeta(
    'expiresAt',
  );
  @override
  late final GeneratedColumn<int> expiresAt = GeneratedColumn<int>(
    'expires_at',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
    defaultValue: const Constant(0),
  );
  @override
  List<GeneratedColumn> get $columns => [contextId, owner, expiresAt];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'sync_workers';
  @override
  VerificationContext validateIntegrity(
    Insertable<SyncWorker> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('context_id')) {
      context.handle(
        _contextIdMeta,
        contextId.isAcceptableOrUnknown(data['context_id']!, _contextIdMeta),
      );
    } else if (isInserting) {
      context.missing(_contextIdMeta);
    }
    if (data.containsKey('owner')) {
      context.handle(
        _ownerMeta,
        owner.isAcceptableOrUnknown(data['owner']!, _ownerMeta),
      );
    }
    if (data.containsKey('expires_at')) {
      context.handle(
        _expiresAtMeta,
        expiresAt.isAcceptableOrUnknown(data['expires_at']!, _expiresAtMeta),
      );
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {contextId};
  @override
  SyncWorker map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return SyncWorker(
      contextId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}context_id'],
      )!,
      owner: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}owner'],
      ),
      expiresAt: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}expires_at'],
      )!,
    );
  }

  @override
  $SyncWorkersTable createAlias(String alias) {
    return $SyncWorkersTable(attachedDatabase, alias);
  }
}

class SyncWorker extends DataClass implements Insertable<SyncWorker> {
  final String contextId;
  final String? owner;
  final int expiresAt;
  const SyncWorker({
    required this.contextId,
    this.owner,
    required this.expiresAt,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['context_id'] = Variable<String>(contextId);
    if (!nullToAbsent || owner != null) {
      map['owner'] = Variable<String>(owner);
    }
    map['expires_at'] = Variable<int>(expiresAt);
    return map;
  }

  SyncWorkersCompanion toCompanion(bool nullToAbsent) {
    return SyncWorkersCompanion(
      contextId: Value(contextId),
      owner: owner == null && nullToAbsent
          ? const Value.absent()
          : Value(owner),
      expiresAt: Value(expiresAt),
    );
  }

  factory SyncWorker.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return SyncWorker(
      contextId: serializer.fromJson<String>(json['contextId']),
      owner: serializer.fromJson<String?>(json['owner']),
      expiresAt: serializer.fromJson<int>(json['expiresAt']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'contextId': serializer.toJson<String>(contextId),
      'owner': serializer.toJson<String?>(owner),
      'expiresAt': serializer.toJson<int>(expiresAt),
    };
  }

  SyncWorker copyWith({
    String? contextId,
    Value<String?> owner = const Value.absent(),
    int? expiresAt,
  }) => SyncWorker(
    contextId: contextId ?? this.contextId,
    owner: owner.present ? owner.value : this.owner,
    expiresAt: expiresAt ?? this.expiresAt,
  );
  SyncWorker copyWithCompanion(SyncWorkersCompanion data) {
    return SyncWorker(
      contextId: data.contextId.present ? data.contextId.value : this.contextId,
      owner: data.owner.present ? data.owner.value : this.owner,
      expiresAt: data.expiresAt.present ? data.expiresAt.value : this.expiresAt,
    );
  }

  @override
  String toString() {
    return (StringBuffer('SyncWorker(')
          ..write('contextId: $contextId, ')
          ..write('owner: $owner, ')
          ..write('expiresAt: $expiresAt')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(contextId, owner, expiresAt);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is SyncWorker &&
          other.contextId == this.contextId &&
          other.owner == this.owner &&
          other.expiresAt == this.expiresAt);
}

class SyncWorkersCompanion extends UpdateCompanion<SyncWorker> {
  final Value<String> contextId;
  final Value<String?> owner;
  final Value<int> expiresAt;
  final Value<int> rowid;
  const SyncWorkersCompanion({
    this.contextId = const Value.absent(),
    this.owner = const Value.absent(),
    this.expiresAt = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  SyncWorkersCompanion.insert({
    required String contextId,
    this.owner = const Value.absent(),
    this.expiresAt = const Value.absent(),
    this.rowid = const Value.absent(),
  }) : contextId = Value(contextId);
  static Insertable<SyncWorker> custom({
    Expression<String>? contextId,
    Expression<String>? owner,
    Expression<int>? expiresAt,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (contextId != null) 'context_id': contextId,
      if (owner != null) 'owner': owner,
      if (expiresAt != null) 'expires_at': expiresAt,
      if (rowid != null) 'rowid': rowid,
    });
  }

  SyncWorkersCompanion copyWith({
    Value<String>? contextId,
    Value<String?>? owner,
    Value<int>? expiresAt,
    Value<int>? rowid,
  }) {
    return SyncWorkersCompanion(
      contextId: contextId ?? this.contextId,
      owner: owner ?? this.owner,
      expiresAt: expiresAt ?? this.expiresAt,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (contextId.present) {
      map['context_id'] = Variable<String>(contextId.value);
    }
    if (owner.present) {
      map['owner'] = Variable<String>(owner.value);
    }
    if (expiresAt.present) {
      map['expires_at'] = Variable<int>(expiresAt.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('SyncWorkersCompanion(')
          ..write('contextId: $contextId, ')
          ..write('owner: $owner, ')
          ..write('expiresAt: $expiresAt, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

abstract class _$PosDatabase extends GeneratedDatabase {
  _$PosDatabase(QueryExecutor e) : super(e);
  $PosDatabaseManager get managers => $PosDatabaseManager(this);
  late final $LocalProductsTable localProducts = $LocalProductsTable(this);
  late final $LocalCustomersTable localCustomers = $LocalCustomersTable(this);
  late final $StockSnapshotsTable stockSnapshots = $StockSnapshotsTable(this);
  late final $AccountSnapshotsTable accountSnapshots = $AccountSnapshotsTable(
    this,
  );
  late final $SyncMetadataTable syncMetadata = $SyncMetadataTable(this);
  late final $DownloadsTable downloads = $DownloadsTable(this);
  late final $DownloadPagesTable downloadPages = $DownloadPagesTable(this);
  late final $LocalEffectsTable localEffects = $LocalEffectsTable(this);
  late final $LocalSalesTable localSales = $LocalSalesTable(this);
  late final $LocalSaleItemsTable localSaleItems = $LocalSaleItemsTable(this);
  late final $LocalPaymentsTable localPayments = $LocalPaymentsTable(this);
  late final $OutboxTable outbox = $OutboxTable(this);
  late final $DeviceSequenceTable deviceSequence = $DeviceSequenceTable(this);
  late final $ReceiptAttemptsTable receiptAttempts = $ReceiptAttemptsTable(
    this,
  );
  late final $SyncWorkersTable syncWorkers = $SyncWorkersTable(this);
  @override
  Iterable<TableInfo<Table, Object?>> get allTables =>
      allSchemaEntities.whereType<TableInfo<Table, Object?>>();
  @override
  List<DatabaseSchemaEntity> get allSchemaEntities => [
    localProducts,
    localCustomers,
    stockSnapshots,
    accountSnapshots,
    syncMetadata,
    downloads,
    downloadPages,
    localEffects,
    localSales,
    localSaleItems,
    localPayments,
    outbox,
    deviceSequence,
    receiptAttempts,
    syncWorkers,
  ];
}

typedef $$LocalProductsTableCreateCompanionBuilder =
    LocalProductsCompanion Function({
      required String serverId,
      required String name,
      required String searchName,
      required String barcode,
      required String size,
      required int priceCents,
      required bool active,
      required String revision,
      Value<int> rowid,
    });
typedef $$LocalProductsTableUpdateCompanionBuilder =
    LocalProductsCompanion Function({
      Value<String> serverId,
      Value<String> name,
      Value<String> searchName,
      Value<String> barcode,
      Value<String> size,
      Value<int> priceCents,
      Value<bool> active,
      Value<String> revision,
      Value<int> rowid,
    });

class $$LocalProductsTableFilterComposer
    extends Composer<_$PosDatabase, $LocalProductsTable> {
  $$LocalProductsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get serverId => $composableBuilder(
    column: $table.serverId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get name => $composableBuilder(
    column: $table.name,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get searchName => $composableBuilder(
    column: $table.searchName,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get barcode => $composableBuilder(
    column: $table.barcode,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get size => $composableBuilder(
    column: $table.size,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get priceCents => $composableBuilder(
    column: $table.priceCents,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<bool> get active => $composableBuilder(
    column: $table.active,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get revision => $composableBuilder(
    column: $table.revision,
    builder: (column) => ColumnFilters(column),
  );
}

class $$LocalProductsTableOrderingComposer
    extends Composer<_$PosDatabase, $LocalProductsTable> {
  $$LocalProductsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get serverId => $composableBuilder(
    column: $table.serverId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get name => $composableBuilder(
    column: $table.name,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get searchName => $composableBuilder(
    column: $table.searchName,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get barcode => $composableBuilder(
    column: $table.barcode,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get size => $composableBuilder(
    column: $table.size,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get priceCents => $composableBuilder(
    column: $table.priceCents,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<bool> get active => $composableBuilder(
    column: $table.active,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get revision => $composableBuilder(
    column: $table.revision,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$LocalProductsTableAnnotationComposer
    extends Composer<_$PosDatabase, $LocalProductsTable> {
  $$LocalProductsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get serverId =>
      $composableBuilder(column: $table.serverId, builder: (column) => column);

  GeneratedColumn<String> get name =>
      $composableBuilder(column: $table.name, builder: (column) => column);

  GeneratedColumn<String> get searchName => $composableBuilder(
    column: $table.searchName,
    builder: (column) => column,
  );

  GeneratedColumn<String> get barcode =>
      $composableBuilder(column: $table.barcode, builder: (column) => column);

  GeneratedColumn<String> get size =>
      $composableBuilder(column: $table.size, builder: (column) => column);

  GeneratedColumn<int> get priceCents => $composableBuilder(
    column: $table.priceCents,
    builder: (column) => column,
  );

  GeneratedColumn<bool> get active =>
      $composableBuilder(column: $table.active, builder: (column) => column);

  GeneratedColumn<String> get revision =>
      $composableBuilder(column: $table.revision, builder: (column) => column);
}

class $$LocalProductsTableTableManager
    extends
        RootTableManager<
          _$PosDatabase,
          $LocalProductsTable,
          LocalProduct,
          $$LocalProductsTableFilterComposer,
          $$LocalProductsTableOrderingComposer,
          $$LocalProductsTableAnnotationComposer,
          $$LocalProductsTableCreateCompanionBuilder,
          $$LocalProductsTableUpdateCompanionBuilder,
          (
            LocalProduct,
            BaseReferences<_$PosDatabase, $LocalProductsTable, LocalProduct>,
          ),
          LocalProduct,
          PrefetchHooks Function()
        > {
  $$LocalProductsTableTableManager(_$PosDatabase db, $LocalProductsTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$LocalProductsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$LocalProductsTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$LocalProductsTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> serverId = const Value.absent(),
                Value<String> name = const Value.absent(),
                Value<String> searchName = const Value.absent(),
                Value<String> barcode = const Value.absent(),
                Value<String> size = const Value.absent(),
                Value<int> priceCents = const Value.absent(),
                Value<bool> active = const Value.absent(),
                Value<String> revision = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => LocalProductsCompanion(
                serverId: serverId,
                name: name,
                searchName: searchName,
                barcode: barcode,
                size: size,
                priceCents: priceCents,
                active: active,
                revision: revision,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String serverId,
                required String name,
                required String searchName,
                required String barcode,
                required String size,
                required int priceCents,
                required bool active,
                required String revision,
                Value<int> rowid = const Value.absent(),
              }) => LocalProductsCompanion.insert(
                serverId: serverId,
                name: name,
                searchName: searchName,
                barcode: barcode,
                size: size,
                priceCents: priceCents,
                active: active,
                revision: revision,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$LocalProductsTable, LocalProduct>(table),
                  BaseReferences<
                    _$PosDatabase,
                    $LocalProductsTable,
                    LocalProduct
                  >(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$LocalProductsTableProcessedTableManager =
    ProcessedTableManager<
      _$PosDatabase,
      $LocalProductsTable,
      LocalProduct,
      $$LocalProductsTableFilterComposer,
      $$LocalProductsTableOrderingComposer,
      $$LocalProductsTableAnnotationComposer,
      $$LocalProductsTableCreateCompanionBuilder,
      $$LocalProductsTableUpdateCompanionBuilder,
      (
        LocalProduct,
        BaseReferences<_$PosDatabase, $LocalProductsTable, LocalProduct>,
      ),
      LocalProduct,
      PrefetchHooks Function()
    >;
typedef $$LocalCustomersTableCreateCompanionBuilder =
    LocalCustomersCompanion Function({
      required String serverId,
      required String name,
      required String searchName,
      required int discountBasisPoints,
      required bool active,
      required String revision,
      Value<int> rowid,
    });
typedef $$LocalCustomersTableUpdateCompanionBuilder =
    LocalCustomersCompanion Function({
      Value<String> serverId,
      Value<String> name,
      Value<String> searchName,
      Value<int> discountBasisPoints,
      Value<bool> active,
      Value<String> revision,
      Value<int> rowid,
    });

class $$LocalCustomersTableFilterComposer
    extends Composer<_$PosDatabase, $LocalCustomersTable> {
  $$LocalCustomersTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get serverId => $composableBuilder(
    column: $table.serverId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get name => $composableBuilder(
    column: $table.name,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get searchName => $composableBuilder(
    column: $table.searchName,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get discountBasisPoints => $composableBuilder(
    column: $table.discountBasisPoints,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<bool> get active => $composableBuilder(
    column: $table.active,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get revision => $composableBuilder(
    column: $table.revision,
    builder: (column) => ColumnFilters(column),
  );
}

class $$LocalCustomersTableOrderingComposer
    extends Composer<_$PosDatabase, $LocalCustomersTable> {
  $$LocalCustomersTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get serverId => $composableBuilder(
    column: $table.serverId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get name => $composableBuilder(
    column: $table.name,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get searchName => $composableBuilder(
    column: $table.searchName,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get discountBasisPoints => $composableBuilder(
    column: $table.discountBasisPoints,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<bool> get active => $composableBuilder(
    column: $table.active,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get revision => $composableBuilder(
    column: $table.revision,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$LocalCustomersTableAnnotationComposer
    extends Composer<_$PosDatabase, $LocalCustomersTable> {
  $$LocalCustomersTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get serverId =>
      $composableBuilder(column: $table.serverId, builder: (column) => column);

  GeneratedColumn<String> get name =>
      $composableBuilder(column: $table.name, builder: (column) => column);

  GeneratedColumn<String> get searchName => $composableBuilder(
    column: $table.searchName,
    builder: (column) => column,
  );

  GeneratedColumn<int> get discountBasisPoints => $composableBuilder(
    column: $table.discountBasisPoints,
    builder: (column) => column,
  );

  GeneratedColumn<bool> get active =>
      $composableBuilder(column: $table.active, builder: (column) => column);

  GeneratedColumn<String> get revision =>
      $composableBuilder(column: $table.revision, builder: (column) => column);
}

class $$LocalCustomersTableTableManager
    extends
        RootTableManager<
          _$PosDatabase,
          $LocalCustomersTable,
          LocalCustomer,
          $$LocalCustomersTableFilterComposer,
          $$LocalCustomersTableOrderingComposer,
          $$LocalCustomersTableAnnotationComposer,
          $$LocalCustomersTableCreateCompanionBuilder,
          $$LocalCustomersTableUpdateCompanionBuilder,
          (
            LocalCustomer,
            BaseReferences<_$PosDatabase, $LocalCustomersTable, LocalCustomer>,
          ),
          LocalCustomer,
          PrefetchHooks Function()
        > {
  $$LocalCustomersTableTableManager(
    _$PosDatabase db,
    $LocalCustomersTable table,
  ) : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$LocalCustomersTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$LocalCustomersTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$LocalCustomersTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> serverId = const Value.absent(),
                Value<String> name = const Value.absent(),
                Value<String> searchName = const Value.absent(),
                Value<int> discountBasisPoints = const Value.absent(),
                Value<bool> active = const Value.absent(),
                Value<String> revision = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => LocalCustomersCompanion(
                serverId: serverId,
                name: name,
                searchName: searchName,
                discountBasisPoints: discountBasisPoints,
                active: active,
                revision: revision,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String serverId,
                required String name,
                required String searchName,
                required int discountBasisPoints,
                required bool active,
                required String revision,
                Value<int> rowid = const Value.absent(),
              }) => LocalCustomersCompanion.insert(
                serverId: serverId,
                name: name,
                searchName: searchName,
                discountBasisPoints: discountBasisPoints,
                active: active,
                revision: revision,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$LocalCustomersTable, LocalCustomer>(table),
                  BaseReferences<
                    _$PosDatabase,
                    $LocalCustomersTable,
                    LocalCustomer
                  >(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$LocalCustomersTableProcessedTableManager =
    ProcessedTableManager<
      _$PosDatabase,
      $LocalCustomersTable,
      LocalCustomer,
      $$LocalCustomersTableFilterComposer,
      $$LocalCustomersTableOrderingComposer,
      $$LocalCustomersTableAnnotationComposer,
      $$LocalCustomersTableCreateCompanionBuilder,
      $$LocalCustomersTableUpdateCompanionBuilder,
      (
        LocalCustomer,
        BaseReferences<_$PosDatabase, $LocalCustomersTable, LocalCustomer>,
      ),
      LocalCustomer,
      PrefetchHooks Function()
    >;
typedef $$StockSnapshotsTableCreateCompanionBuilder =
    StockSnapshotsCompanion Function({
      required String productId,
      required int quantityUnits,
      required String revision,
      Value<int> rowid,
    });
typedef $$StockSnapshotsTableUpdateCompanionBuilder =
    StockSnapshotsCompanion Function({
      Value<String> productId,
      Value<int> quantityUnits,
      Value<String> revision,
      Value<int> rowid,
    });

class $$StockSnapshotsTableFilterComposer
    extends Composer<_$PosDatabase, $StockSnapshotsTable> {
  $$StockSnapshotsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get productId => $composableBuilder(
    column: $table.productId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get quantityUnits => $composableBuilder(
    column: $table.quantityUnits,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get revision => $composableBuilder(
    column: $table.revision,
    builder: (column) => ColumnFilters(column),
  );
}

class $$StockSnapshotsTableOrderingComposer
    extends Composer<_$PosDatabase, $StockSnapshotsTable> {
  $$StockSnapshotsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get productId => $composableBuilder(
    column: $table.productId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get quantityUnits => $composableBuilder(
    column: $table.quantityUnits,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get revision => $composableBuilder(
    column: $table.revision,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$StockSnapshotsTableAnnotationComposer
    extends Composer<_$PosDatabase, $StockSnapshotsTable> {
  $$StockSnapshotsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get productId =>
      $composableBuilder(column: $table.productId, builder: (column) => column);

  GeneratedColumn<int> get quantityUnits => $composableBuilder(
    column: $table.quantityUnits,
    builder: (column) => column,
  );

  GeneratedColumn<String> get revision =>
      $composableBuilder(column: $table.revision, builder: (column) => column);
}

class $$StockSnapshotsTableTableManager
    extends
        RootTableManager<
          _$PosDatabase,
          $StockSnapshotsTable,
          StockSnapshot,
          $$StockSnapshotsTableFilterComposer,
          $$StockSnapshotsTableOrderingComposer,
          $$StockSnapshotsTableAnnotationComposer,
          $$StockSnapshotsTableCreateCompanionBuilder,
          $$StockSnapshotsTableUpdateCompanionBuilder,
          (
            StockSnapshot,
            BaseReferences<_$PosDatabase, $StockSnapshotsTable, StockSnapshot>,
          ),
          StockSnapshot,
          PrefetchHooks Function()
        > {
  $$StockSnapshotsTableTableManager(
    _$PosDatabase db,
    $StockSnapshotsTable table,
  ) : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$StockSnapshotsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$StockSnapshotsTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$StockSnapshotsTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> productId = const Value.absent(),
                Value<int> quantityUnits = const Value.absent(),
                Value<String> revision = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => StockSnapshotsCompanion(
                productId: productId,
                quantityUnits: quantityUnits,
                revision: revision,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String productId,
                required int quantityUnits,
                required String revision,
                Value<int> rowid = const Value.absent(),
              }) => StockSnapshotsCompanion.insert(
                productId: productId,
                quantityUnits: quantityUnits,
                revision: revision,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$StockSnapshotsTable, StockSnapshot>(table),
                  BaseReferences<
                    _$PosDatabase,
                    $StockSnapshotsTable,
                    StockSnapshot
                  >(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$StockSnapshotsTableProcessedTableManager =
    ProcessedTableManager<
      _$PosDatabase,
      $StockSnapshotsTable,
      StockSnapshot,
      $$StockSnapshotsTableFilterComposer,
      $$StockSnapshotsTableOrderingComposer,
      $$StockSnapshotsTableAnnotationComposer,
      $$StockSnapshotsTableCreateCompanionBuilder,
      $$StockSnapshotsTableUpdateCompanionBuilder,
      (
        StockSnapshot,
        BaseReferences<_$PosDatabase, $StockSnapshotsTable, StockSnapshot>,
      ),
      StockSnapshot,
      PrefetchHooks Function()
    >;
typedef $$AccountSnapshotsTableCreateCompanionBuilder =
    AccountSnapshotsCompanion Function({
      required String customerId,
      required int debtCents,
      required int paymentCents,
      required int balanceCents,
      required String revision,
      Value<int> rowid,
    });
typedef $$AccountSnapshotsTableUpdateCompanionBuilder =
    AccountSnapshotsCompanion Function({
      Value<String> customerId,
      Value<int> debtCents,
      Value<int> paymentCents,
      Value<int> balanceCents,
      Value<String> revision,
      Value<int> rowid,
    });

class $$AccountSnapshotsTableFilterComposer
    extends Composer<_$PosDatabase, $AccountSnapshotsTable> {
  $$AccountSnapshotsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get customerId => $composableBuilder(
    column: $table.customerId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get debtCents => $composableBuilder(
    column: $table.debtCents,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get paymentCents => $composableBuilder(
    column: $table.paymentCents,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get balanceCents => $composableBuilder(
    column: $table.balanceCents,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get revision => $composableBuilder(
    column: $table.revision,
    builder: (column) => ColumnFilters(column),
  );
}

class $$AccountSnapshotsTableOrderingComposer
    extends Composer<_$PosDatabase, $AccountSnapshotsTable> {
  $$AccountSnapshotsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get customerId => $composableBuilder(
    column: $table.customerId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get debtCents => $composableBuilder(
    column: $table.debtCents,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get paymentCents => $composableBuilder(
    column: $table.paymentCents,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get balanceCents => $composableBuilder(
    column: $table.balanceCents,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get revision => $composableBuilder(
    column: $table.revision,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$AccountSnapshotsTableAnnotationComposer
    extends Composer<_$PosDatabase, $AccountSnapshotsTable> {
  $$AccountSnapshotsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get customerId => $composableBuilder(
    column: $table.customerId,
    builder: (column) => column,
  );

  GeneratedColumn<int> get debtCents =>
      $composableBuilder(column: $table.debtCents, builder: (column) => column);

  GeneratedColumn<int> get paymentCents => $composableBuilder(
    column: $table.paymentCents,
    builder: (column) => column,
  );

  GeneratedColumn<int> get balanceCents => $composableBuilder(
    column: $table.balanceCents,
    builder: (column) => column,
  );

  GeneratedColumn<String> get revision =>
      $composableBuilder(column: $table.revision, builder: (column) => column);
}

class $$AccountSnapshotsTableTableManager
    extends
        RootTableManager<
          _$PosDatabase,
          $AccountSnapshotsTable,
          AccountSnapshot,
          $$AccountSnapshotsTableFilterComposer,
          $$AccountSnapshotsTableOrderingComposer,
          $$AccountSnapshotsTableAnnotationComposer,
          $$AccountSnapshotsTableCreateCompanionBuilder,
          $$AccountSnapshotsTableUpdateCompanionBuilder,
          (
            AccountSnapshot,
            BaseReferences<
              _$PosDatabase,
              $AccountSnapshotsTable,
              AccountSnapshot
            >,
          ),
          AccountSnapshot,
          PrefetchHooks Function()
        > {
  $$AccountSnapshotsTableTableManager(
    _$PosDatabase db,
    $AccountSnapshotsTable table,
  ) : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$AccountSnapshotsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$AccountSnapshotsTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$AccountSnapshotsTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> customerId = const Value.absent(),
                Value<int> debtCents = const Value.absent(),
                Value<int> paymentCents = const Value.absent(),
                Value<int> balanceCents = const Value.absent(),
                Value<String> revision = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => AccountSnapshotsCompanion(
                customerId: customerId,
                debtCents: debtCents,
                paymentCents: paymentCents,
                balanceCents: balanceCents,
                revision: revision,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String customerId,
                required int debtCents,
                required int paymentCents,
                required int balanceCents,
                required String revision,
                Value<int> rowid = const Value.absent(),
              }) => AccountSnapshotsCompanion.insert(
                customerId: customerId,
                debtCents: debtCents,
                paymentCents: paymentCents,
                balanceCents: balanceCents,
                revision: revision,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$AccountSnapshotsTable, AccountSnapshot>(table),
                  BaseReferences<
                    _$PosDatabase,
                    $AccountSnapshotsTable,
                    AccountSnapshot
                  >(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$AccountSnapshotsTableProcessedTableManager =
    ProcessedTableManager<
      _$PosDatabase,
      $AccountSnapshotsTable,
      AccountSnapshot,
      $$AccountSnapshotsTableFilterComposer,
      $$AccountSnapshotsTableOrderingComposer,
      $$AccountSnapshotsTableAnnotationComposer,
      $$AccountSnapshotsTableCreateCompanionBuilder,
      $$AccountSnapshotsTableUpdateCompanionBuilder,
      (
        AccountSnapshot,
        BaseReferences<_$PosDatabase, $AccountSnapshotsTable, AccountSnapshot>,
      ),
      AccountSnapshot,
      PrefetchHooks Function()
    >;
typedef $$SyncMetadataTableCreateCompanionBuilder =
    SyncMetadataCompanion Function({
      Value<int> id,
      required String contextId,
      Value<String?> cursor,
      Value<String?> revision,
      Value<String?> syncedAt,
      Value<String?> lastObservedAt,
      Value<String?> defaultCustomerId,
      Value<bool> authLocked,
    });
typedef $$SyncMetadataTableUpdateCompanionBuilder =
    SyncMetadataCompanion Function({
      Value<int> id,
      Value<String> contextId,
      Value<String?> cursor,
      Value<String?> revision,
      Value<String?> syncedAt,
      Value<String?> lastObservedAt,
      Value<String?> defaultCustomerId,
      Value<bool> authLocked,
    });

class $$SyncMetadataTableFilterComposer
    extends Composer<_$PosDatabase, $SyncMetadataTable> {
  $$SyncMetadataTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get contextId => $composableBuilder(
    column: $table.contextId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get cursor => $composableBuilder(
    column: $table.cursor,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get revision => $composableBuilder(
    column: $table.revision,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get syncedAt => $composableBuilder(
    column: $table.syncedAt,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get lastObservedAt => $composableBuilder(
    column: $table.lastObservedAt,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get defaultCustomerId => $composableBuilder(
    column: $table.defaultCustomerId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<bool> get authLocked => $composableBuilder(
    column: $table.authLocked,
    builder: (column) => ColumnFilters(column),
  );
}

class $$SyncMetadataTableOrderingComposer
    extends Composer<_$PosDatabase, $SyncMetadataTable> {
  $$SyncMetadataTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get contextId => $composableBuilder(
    column: $table.contextId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get cursor => $composableBuilder(
    column: $table.cursor,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get revision => $composableBuilder(
    column: $table.revision,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get syncedAt => $composableBuilder(
    column: $table.syncedAt,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get lastObservedAt => $composableBuilder(
    column: $table.lastObservedAt,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get defaultCustomerId => $composableBuilder(
    column: $table.defaultCustomerId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<bool> get authLocked => $composableBuilder(
    column: $table.authLocked,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$SyncMetadataTableAnnotationComposer
    extends Composer<_$PosDatabase, $SyncMetadataTable> {
  $$SyncMetadataTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<String> get contextId =>
      $composableBuilder(column: $table.contextId, builder: (column) => column);

  GeneratedColumn<String> get cursor =>
      $composableBuilder(column: $table.cursor, builder: (column) => column);

  GeneratedColumn<String> get revision =>
      $composableBuilder(column: $table.revision, builder: (column) => column);

  GeneratedColumn<String> get syncedAt =>
      $composableBuilder(column: $table.syncedAt, builder: (column) => column);

  GeneratedColumn<String> get lastObservedAt => $composableBuilder(
    column: $table.lastObservedAt,
    builder: (column) => column,
  );

  GeneratedColumn<String> get defaultCustomerId => $composableBuilder(
    column: $table.defaultCustomerId,
    builder: (column) => column,
  );

  GeneratedColumn<bool> get authLocked => $composableBuilder(
    column: $table.authLocked,
    builder: (column) => column,
  );
}

class $$SyncMetadataTableTableManager
    extends
        RootTableManager<
          _$PosDatabase,
          $SyncMetadataTable,
          SyncMetadataData,
          $$SyncMetadataTableFilterComposer,
          $$SyncMetadataTableOrderingComposer,
          $$SyncMetadataTableAnnotationComposer,
          $$SyncMetadataTableCreateCompanionBuilder,
          $$SyncMetadataTableUpdateCompanionBuilder,
          (
            SyncMetadataData,
            BaseReferences<_$PosDatabase, $SyncMetadataTable, SyncMetadataData>,
          ),
          SyncMetadataData,
          PrefetchHooks Function()
        > {
  $$SyncMetadataTableTableManager(_$PosDatabase db, $SyncMetadataTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$SyncMetadataTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$SyncMetadataTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$SyncMetadataTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<int> id = const Value.absent(),
                Value<String> contextId = const Value.absent(),
                Value<String?> cursor = const Value.absent(),
                Value<String?> revision = const Value.absent(),
                Value<String?> syncedAt = const Value.absent(),
                Value<String?> lastObservedAt = const Value.absent(),
                Value<String?> defaultCustomerId = const Value.absent(),
                Value<bool> authLocked = const Value.absent(),
              }) => SyncMetadataCompanion(
                id: id,
                contextId: contextId,
                cursor: cursor,
                revision: revision,
                syncedAt: syncedAt,
                lastObservedAt: lastObservedAt,
                defaultCustomerId: defaultCustomerId,
                authLocked: authLocked,
              ),
          createCompanionCallback:
              ({
                Value<int> id = const Value.absent(),
                required String contextId,
                Value<String?> cursor = const Value.absent(),
                Value<String?> revision = const Value.absent(),
                Value<String?> syncedAt = const Value.absent(),
                Value<String?> lastObservedAt = const Value.absent(),
                Value<String?> defaultCustomerId = const Value.absent(),
                Value<bool> authLocked = const Value.absent(),
              }) => SyncMetadataCompanion.insert(
                id: id,
                contextId: contextId,
                cursor: cursor,
                revision: revision,
                syncedAt: syncedAt,
                lastObservedAt: lastObservedAt,
                defaultCustomerId: defaultCustomerId,
                authLocked: authLocked,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$SyncMetadataTable, SyncMetadataData>(table),
                  BaseReferences<
                    _$PosDatabase,
                    $SyncMetadataTable,
                    SyncMetadataData
                  >(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$SyncMetadataTableProcessedTableManager =
    ProcessedTableManager<
      _$PosDatabase,
      $SyncMetadataTable,
      SyncMetadataData,
      $$SyncMetadataTableFilterComposer,
      $$SyncMetadataTableOrderingComposer,
      $$SyncMetadataTableAnnotationComposer,
      $$SyncMetadataTableCreateCompanionBuilder,
      $$SyncMetadataTableUpdateCompanionBuilder,
      (
        SyncMetadataData,
        BaseReferences<_$PosDatabase, $SyncMetadataTable, SyncMetadataData>,
      ),
      SyncMetadataData,
      PrefetchHooks Function()
    >;
typedef $$DownloadsTableCreateCompanionBuilder = DownloadsCompanion Function({
  Value<int> id,
  required bool incremental,
  Value<String?> baseCursor,
  Value<String?> revision,
  Value<String?> nextPageToken,
  Value<int> pageCount,
  Value<bool> complete,
});
typedef $$DownloadsTableUpdateCompanionBuilder = DownloadsCompanion Function({
  Value<int> id,
  Value<bool> incremental,
  Value<String?> baseCursor,
  Value<String?> revision,
  Value<String?> nextPageToken,
  Value<int> pageCount,
  Value<bool> complete,
});

class $$DownloadsTableFilterComposer
    extends Composer<_$PosDatabase, $DownloadsTable> {
  $$DownloadsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<bool> get incremental => $composableBuilder(
    column: $table.incremental,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get baseCursor => $composableBuilder(
    column: $table.baseCursor,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get revision => $composableBuilder(
    column: $table.revision,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get nextPageToken => $composableBuilder(
    column: $table.nextPageToken,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get pageCount => $composableBuilder(
    column: $table.pageCount,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<bool> get complete => $composableBuilder(
    column: $table.complete,
    builder: (column) => ColumnFilters(column),
  );
}

class $$DownloadsTableOrderingComposer
    extends Composer<_$PosDatabase, $DownloadsTable> {
  $$DownloadsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<bool> get incremental => $composableBuilder(
    column: $table.incremental,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get baseCursor => $composableBuilder(
    column: $table.baseCursor,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get revision => $composableBuilder(
    column: $table.revision,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get nextPageToken => $composableBuilder(
    column: $table.nextPageToken,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get pageCount => $composableBuilder(
    column: $table.pageCount,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<bool> get complete => $composableBuilder(
    column: $table.complete,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$DownloadsTableAnnotationComposer
    extends Composer<_$PosDatabase, $DownloadsTable> {
  $$DownloadsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<bool> get incremental => $composableBuilder(
    column: $table.incremental,
    builder: (column) => column,
  );

  GeneratedColumn<String> get baseCursor => $composableBuilder(
    column: $table.baseCursor,
    builder: (column) => column,
  );

  GeneratedColumn<String> get revision =>
      $composableBuilder(column: $table.revision, builder: (column) => column);

  GeneratedColumn<String> get nextPageToken => $composableBuilder(
    column: $table.nextPageToken,
    builder: (column) => column,
  );

  GeneratedColumn<int> get pageCount =>
      $composableBuilder(column: $table.pageCount, builder: (column) => column);

  GeneratedColumn<bool> get complete =>
      $composableBuilder(column: $table.complete, builder: (column) => column);
}

class $$DownloadsTableTableManager
    extends
        RootTableManager<
          _$PosDatabase,
          $DownloadsTable,
          Download,
          $$DownloadsTableFilterComposer,
          $$DownloadsTableOrderingComposer,
          $$DownloadsTableAnnotationComposer,
          $$DownloadsTableCreateCompanionBuilder,
          $$DownloadsTableUpdateCompanionBuilder,
          (Download, BaseReferences<_$PosDatabase, $DownloadsTable, Download>),
          Download,
          PrefetchHooks Function()
        > {
  $$DownloadsTableTableManager(_$PosDatabase db, $DownloadsTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$DownloadsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$DownloadsTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$DownloadsTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<int> id = const Value.absent(),
                Value<bool> incremental = const Value.absent(),
                Value<String?> baseCursor = const Value.absent(),
                Value<String?> revision = const Value.absent(),
                Value<String?> nextPageToken = const Value.absent(),
                Value<int> pageCount = const Value.absent(),
                Value<bool> complete = const Value.absent(),
              }) => DownloadsCompanion(
                id: id,
                incremental: incremental,
                baseCursor: baseCursor,
                revision: revision,
                nextPageToken: nextPageToken,
                pageCount: pageCount,
                complete: complete,
              ),
          createCompanionCallback:
              ({
                Value<int> id = const Value.absent(),
                required bool incremental,
                Value<String?> baseCursor = const Value.absent(),
                Value<String?> revision = const Value.absent(),
                Value<String?> nextPageToken = const Value.absent(),
                Value<int> pageCount = const Value.absent(),
                Value<bool> complete = const Value.absent(),
              }) => DownloadsCompanion.insert(
                id: id,
                incremental: incremental,
                baseCursor: baseCursor,
                revision: revision,
                nextPageToken: nextPageToken,
                pageCount: pageCount,
                complete: complete,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$DownloadsTable, Download>(table),
                  BaseReferences<_$PosDatabase, $DownloadsTable, Download>(
                    db,
                    table,
                    e,
                  ),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$DownloadsTableProcessedTableManager =
    ProcessedTableManager<
      _$PosDatabase,
      $DownloadsTable,
      Download,
      $$DownloadsTableFilterComposer,
      $$DownloadsTableOrderingComposer,
      $$DownloadsTableAnnotationComposer,
      $$DownloadsTableCreateCompanionBuilder,
      $$DownloadsTableUpdateCompanionBuilder,
      (Download, BaseReferences<_$PosDatabase, $DownloadsTable, Download>),
      Download,
      PrefetchHooks Function()
    >;
typedef $$DownloadPagesTableCreateCompanionBuilder =
    DownloadPagesCompanion Function({
      Value<int> ordinal,
      required String payload,
    });
typedef $$DownloadPagesTableUpdateCompanionBuilder =
    DownloadPagesCompanion Function({
      Value<int> ordinal,
      Value<String> payload,
    });

class $$DownloadPagesTableFilterComposer
    extends Composer<_$PosDatabase, $DownloadPagesTable> {
  $$DownloadPagesTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get ordinal => $composableBuilder(
    column: $table.ordinal,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get payload => $composableBuilder(
    column: $table.payload,
    builder: (column) => ColumnFilters(column),
  );
}

class $$DownloadPagesTableOrderingComposer
    extends Composer<_$PosDatabase, $DownloadPagesTable> {
  $$DownloadPagesTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get ordinal => $composableBuilder(
    column: $table.ordinal,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get payload => $composableBuilder(
    column: $table.payload,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$DownloadPagesTableAnnotationComposer
    extends Composer<_$PosDatabase, $DownloadPagesTable> {
  $$DownloadPagesTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get ordinal =>
      $composableBuilder(column: $table.ordinal, builder: (column) => column);

  GeneratedColumn<String> get payload =>
      $composableBuilder(column: $table.payload, builder: (column) => column);
}

class $$DownloadPagesTableTableManager
    extends
        RootTableManager<
          _$PosDatabase,
          $DownloadPagesTable,
          DownloadPage,
          $$DownloadPagesTableFilterComposer,
          $$DownloadPagesTableOrderingComposer,
          $$DownloadPagesTableAnnotationComposer,
          $$DownloadPagesTableCreateCompanionBuilder,
          $$DownloadPagesTableUpdateCompanionBuilder,
          (
            DownloadPage,
            BaseReferences<_$PosDatabase, $DownloadPagesTable, DownloadPage>,
          ),
          DownloadPage,
          PrefetchHooks Function()
        > {
  $$DownloadPagesTableTableManager(_$PosDatabase db, $DownloadPagesTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$DownloadPagesTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$DownloadPagesTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$DownloadPagesTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback: ({
            Value<int> ordinal = const Value.absent(),
            Value<String> payload = const Value.absent(),
          }) => DownloadPagesCompanion(ordinal: ordinal, payload: payload),
          createCompanionCallback:
              ({
                Value<int> ordinal = const Value.absent(),
                required String payload,
              }) => DownloadPagesCompanion.insert(
                ordinal: ordinal,
                payload: payload,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$DownloadPagesTable, DownloadPage>(table),
                  BaseReferences<
                    _$PosDatabase,
                    $DownloadPagesTable,
                    DownloadPage
                  >(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$DownloadPagesTableProcessedTableManager =
    ProcessedTableManager<
      _$PosDatabase,
      $DownloadPagesTable,
      DownloadPage,
      $$DownloadPagesTableFilterComposer,
      $$DownloadPagesTableOrderingComposer,
      $$DownloadPagesTableAnnotationComposer,
      $$DownloadPagesTableCreateCompanionBuilder,
      $$DownloadPagesTableUpdateCompanionBuilder,
      (
        DownloadPage,
        BaseReferences<_$PosDatabase, $DownloadPagesTable, DownloadPage>,
      ),
      DownloadPage,
      PrefetchHooks Function()
    >;
typedef $$LocalEffectsTableCreateCompanionBuilder =
    LocalEffectsCompanion Function({
      required String operationId,
      required String entityId,
      required String kind,
      Value<int> stockDelta,
      Value<int> debtDelta,
      Value<int> paymentDelta,
      Value<bool> reflected,
      Value<String?> originalStatus,
      Value<int> rowid,
    });
typedef $$LocalEffectsTableUpdateCompanionBuilder =
    LocalEffectsCompanion Function({
      Value<String> operationId,
      Value<String> entityId,
      Value<String> kind,
      Value<int> stockDelta,
      Value<int> debtDelta,
      Value<int> paymentDelta,
      Value<bool> reflected,
      Value<String?> originalStatus,
      Value<int> rowid,
    });

class $$LocalEffectsTableFilterComposer
    extends Composer<_$PosDatabase, $LocalEffectsTable> {
  $$LocalEffectsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get operationId => $composableBuilder(
    column: $table.operationId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get entityId => $composableBuilder(
    column: $table.entityId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get kind => $composableBuilder(
    column: $table.kind,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get stockDelta => $composableBuilder(
    column: $table.stockDelta,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get debtDelta => $composableBuilder(
    column: $table.debtDelta,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get paymentDelta => $composableBuilder(
    column: $table.paymentDelta,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<bool> get reflected => $composableBuilder(
    column: $table.reflected,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get originalStatus => $composableBuilder(
    column: $table.originalStatus,
    builder: (column) => ColumnFilters(column),
  );
}

class $$LocalEffectsTableOrderingComposer
    extends Composer<_$PosDatabase, $LocalEffectsTable> {
  $$LocalEffectsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get operationId => $composableBuilder(
    column: $table.operationId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get entityId => $composableBuilder(
    column: $table.entityId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get kind => $composableBuilder(
    column: $table.kind,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get stockDelta => $composableBuilder(
    column: $table.stockDelta,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get debtDelta => $composableBuilder(
    column: $table.debtDelta,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get paymentDelta => $composableBuilder(
    column: $table.paymentDelta,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<bool> get reflected => $composableBuilder(
    column: $table.reflected,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get originalStatus => $composableBuilder(
    column: $table.originalStatus,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$LocalEffectsTableAnnotationComposer
    extends Composer<_$PosDatabase, $LocalEffectsTable> {
  $$LocalEffectsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get operationId => $composableBuilder(
    column: $table.operationId,
    builder: (column) => column,
  );

  GeneratedColumn<String> get entityId =>
      $composableBuilder(column: $table.entityId, builder: (column) => column);

  GeneratedColumn<String> get kind =>
      $composableBuilder(column: $table.kind, builder: (column) => column);

  GeneratedColumn<int> get stockDelta => $composableBuilder(
    column: $table.stockDelta,
    builder: (column) => column,
  );

  GeneratedColumn<int> get debtDelta =>
      $composableBuilder(column: $table.debtDelta, builder: (column) => column);

  GeneratedColumn<int> get paymentDelta => $composableBuilder(
    column: $table.paymentDelta,
    builder: (column) => column,
  );

  GeneratedColumn<bool> get reflected =>
      $composableBuilder(column: $table.reflected, builder: (column) => column);

  GeneratedColumn<String> get originalStatus => $composableBuilder(
    column: $table.originalStatus,
    builder: (column) => column,
  );
}

class $$LocalEffectsTableTableManager
    extends
        RootTableManager<
          _$PosDatabase,
          $LocalEffectsTable,
          LocalEffect,
          $$LocalEffectsTableFilterComposer,
          $$LocalEffectsTableOrderingComposer,
          $$LocalEffectsTableAnnotationComposer,
          $$LocalEffectsTableCreateCompanionBuilder,
          $$LocalEffectsTableUpdateCompanionBuilder,
          (
            LocalEffect,
            BaseReferences<_$PosDatabase, $LocalEffectsTable, LocalEffect>,
          ),
          LocalEffect,
          PrefetchHooks Function()
        > {
  $$LocalEffectsTableTableManager(_$PosDatabase db, $LocalEffectsTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$LocalEffectsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$LocalEffectsTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$LocalEffectsTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> operationId = const Value.absent(),
                Value<String> entityId = const Value.absent(),
                Value<String> kind = const Value.absent(),
                Value<int> stockDelta = const Value.absent(),
                Value<int> debtDelta = const Value.absent(),
                Value<int> paymentDelta = const Value.absent(),
                Value<bool> reflected = const Value.absent(),
                Value<String?> originalStatus = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => LocalEffectsCompanion(
                operationId: operationId,
                entityId: entityId,
                kind: kind,
                stockDelta: stockDelta,
                debtDelta: debtDelta,
                paymentDelta: paymentDelta,
                reflected: reflected,
                originalStatus: originalStatus,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String operationId,
                required String entityId,
                required String kind,
                Value<int> stockDelta = const Value.absent(),
                Value<int> debtDelta = const Value.absent(),
                Value<int> paymentDelta = const Value.absent(),
                Value<bool> reflected = const Value.absent(),
                Value<String?> originalStatus = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => LocalEffectsCompanion.insert(
                operationId: operationId,
                entityId: entityId,
                kind: kind,
                stockDelta: stockDelta,
                debtDelta: debtDelta,
                paymentDelta: paymentDelta,
                reflected: reflected,
                originalStatus: originalStatus,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$LocalEffectsTable, LocalEffect>(table),
                  BaseReferences<
                    _$PosDatabase,
                    $LocalEffectsTable,
                    LocalEffect
                  >(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$LocalEffectsTableProcessedTableManager =
    ProcessedTableManager<
      _$PosDatabase,
      $LocalEffectsTable,
      LocalEffect,
      $$LocalEffectsTableFilterComposer,
      $$LocalEffectsTableOrderingComposer,
      $$LocalEffectsTableAnnotationComposer,
      $$LocalEffectsTableCreateCompanionBuilder,
      $$LocalEffectsTableUpdateCompanionBuilder,
      (
        LocalEffect,
        BaseReferences<_$PosDatabase, $LocalEffectsTable, LocalEffect>,
      ),
      LocalEffect,
      PrefetchHooks Function()
    >;
typedef $$LocalSalesTableCreateCompanionBuilder = LocalSalesCompanion Function({
  required String id,
  required String operationId,
  required String contextId,
  required int sequence,
  required String localFolio,
  Value<String?> serverFolio,
  required String customerId,
  required String customerName,
  required String branchName,
  required String operatorName,
  required String saleType,
  required int discountBasisPoints,
  required int totalCents,
  required int appliedCents,
  required int receivedCents,
  required int changeCents,
  required int balanceCents,
  required bool paid,
  Value<String> status,
  required String occurredAt,
  required String offlineLeaseId,
  required String catalogRevision,
  required String requestHash,
  Value<int> rowid,
});
typedef $$LocalSalesTableUpdateCompanionBuilder = LocalSalesCompanion Function({
  Value<String> id,
  Value<String> operationId,
  Value<String> contextId,
  Value<int> sequence,
  Value<String> localFolio,
  Value<String?> serverFolio,
  Value<String> customerId,
  Value<String> customerName,
  Value<String> branchName,
  Value<String> operatorName,
  Value<String> saleType,
  Value<int> discountBasisPoints,
  Value<int> totalCents,
  Value<int> appliedCents,
  Value<int> receivedCents,
  Value<int> changeCents,
  Value<int> balanceCents,
  Value<bool> paid,
  Value<String> status,
  Value<String> occurredAt,
  Value<String> offlineLeaseId,
  Value<String> catalogRevision,
  Value<String> requestHash,
  Value<int> rowid,
});

final class $$LocalSalesTableReferences
    extends BaseReferences<_$PosDatabase, $LocalSalesTable, LocalSale> {
  $$LocalSalesTableReferences(super.$_db, super.$_table, super.$_typedResult);

  static MultiTypedResultKey<$LocalSaleItemsTable, List<LocalSaleItem>>
  _localSaleItemsRefsTable(_$PosDatabase db) => MultiTypedResultKey.fromTable(
    db.localSaleItems,
    aliasName: 'local_sales__id__local_sale_items__sale_id',
  );

  $$LocalSaleItemsTableProcessedTableManager get localSaleItemsRefs {
    final manager = $$LocalSaleItemsTableTableManager(
      $_db,
      $_db.localSaleItems,
    ).filter((f) => f.saleId.id.sqlEquals($_itemColumn<String>('id')!));

    final cache = $_typedResult.readTableOrNull(_localSaleItemsRefsTable($_db));
    return ProcessedTableManager(
      manager.$state.copyWith(prefetchedData: cache),
    );
  }

  static MultiTypedResultKey<$LocalPaymentsTable, List<LocalPayment>>
  _localPaymentsRefsTable(_$PosDatabase db) => MultiTypedResultKey.fromTable(
    db.localPayments,
    aliasName: 'local_sales__id__local_payments__sale_id',
  );

  $$LocalPaymentsTableProcessedTableManager get localPaymentsRefs {
    final manager = $$LocalPaymentsTableTableManager(
      $_db,
      $_db.localPayments,
    ).filter((f) => f.saleId.id.sqlEquals($_itemColumn<String>('id')!));

    final cache = $_typedResult.readTableOrNull(_localPaymentsRefsTable($_db));
    return ProcessedTableManager(
      manager.$state.copyWith(prefetchedData: cache),
    );
  }

  static MultiTypedResultKey<$OutboxTable, List<OutboxData>> _outboxRefsTable(
    _$PosDatabase db,
  ) => MultiTypedResultKey.fromTable(
    db.outbox,
    aliasName: 'local_sales__id__outbox__sale_id',
  );

  $$OutboxTableProcessedTableManager get outboxRefs {
    final manager = $$OutboxTableTableManager(
      $_db,
      $_db.outbox,
    ).filter((f) => f.saleId.id.sqlEquals($_itemColumn<String>('id')!));

    final cache = $_typedResult.readTableOrNull(_outboxRefsTable($_db));
    return ProcessedTableManager(
      manager.$state.copyWith(prefetchedData: cache),
    );
  }

  static MultiTypedResultKey<$ReceiptAttemptsTable, List<ReceiptAttempt>>
  _receiptAttemptsRefsTable(_$PosDatabase db) => MultiTypedResultKey.fromTable(
    db.receiptAttempts,
    aliasName: 'local_sales__id__receipt_attempts__sale_id',
  );

  $$ReceiptAttemptsTableProcessedTableManager get receiptAttemptsRefs {
    final manager = $$ReceiptAttemptsTableTableManager(
      $_db,
      $_db.receiptAttempts,
    ).filter((f) => f.saleId.id.sqlEquals($_itemColumn<String>('id')!));

    final cache = $_typedResult.readTableOrNull(
      _receiptAttemptsRefsTable($_db),
    );
    return ProcessedTableManager(
      manager.$state.copyWith(prefetchedData: cache),
    );
  }
}

class $$LocalSalesTableFilterComposer
    extends Composer<_$PosDatabase, $LocalSalesTable> {
  $$LocalSalesTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get operationId => $composableBuilder(
    column: $table.operationId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get contextId => $composableBuilder(
    column: $table.contextId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get sequence => $composableBuilder(
    column: $table.sequence,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get localFolio => $composableBuilder(
    column: $table.localFolio,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get serverFolio => $composableBuilder(
    column: $table.serverFolio,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get customerId => $composableBuilder(
    column: $table.customerId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get customerName => $composableBuilder(
    column: $table.customerName,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get branchName => $composableBuilder(
    column: $table.branchName,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get operatorName => $composableBuilder(
    column: $table.operatorName,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get saleType => $composableBuilder(
    column: $table.saleType,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get discountBasisPoints => $composableBuilder(
    column: $table.discountBasisPoints,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get totalCents => $composableBuilder(
    column: $table.totalCents,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get appliedCents => $composableBuilder(
    column: $table.appliedCents,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get receivedCents => $composableBuilder(
    column: $table.receivedCents,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get changeCents => $composableBuilder(
    column: $table.changeCents,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get balanceCents => $composableBuilder(
    column: $table.balanceCents,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<bool> get paid => $composableBuilder(
    column: $table.paid,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get occurredAt => $composableBuilder(
    column: $table.occurredAt,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get offlineLeaseId => $composableBuilder(
    column: $table.offlineLeaseId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get catalogRevision => $composableBuilder(
    column: $table.catalogRevision,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get requestHash => $composableBuilder(
    column: $table.requestHash,
    builder: (column) => ColumnFilters(column),
  );

  Expression<bool> localSaleItemsRefs(
    Expression<bool> Function($$LocalSaleItemsTableFilterComposer f) f,
  ) {
    final $$LocalSaleItemsTableFilterComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.id,
      referencedTable: $db.localSaleItems,
      getReferencedColumn: (t) => t.saleId,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$LocalSaleItemsTableFilterComposer(
            $db: $db,
            $table: $db.localSaleItems,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return f(composer);
  }

  Expression<bool> localPaymentsRefs(
    Expression<bool> Function($$LocalPaymentsTableFilterComposer f) f,
  ) {
    final $$LocalPaymentsTableFilterComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.id,
      referencedTable: $db.localPayments,
      getReferencedColumn: (t) => t.saleId,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$LocalPaymentsTableFilterComposer(
            $db: $db,
            $table: $db.localPayments,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return f(composer);
  }

  Expression<bool> outboxRefs(
    Expression<bool> Function($$OutboxTableFilterComposer f) f,
  ) {
    final $$OutboxTableFilterComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.id,
      referencedTable: $db.outbox,
      getReferencedColumn: (t) => t.saleId,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$OutboxTableFilterComposer(
            $db: $db,
            $table: $db.outbox,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return f(composer);
  }

  Expression<bool> receiptAttemptsRefs(
    Expression<bool> Function($$ReceiptAttemptsTableFilterComposer f) f,
  ) {
    final $$ReceiptAttemptsTableFilterComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.id,
      referencedTable: $db.receiptAttempts,
      getReferencedColumn: (t) => t.saleId,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$ReceiptAttemptsTableFilterComposer(
            $db: $db,
            $table: $db.receiptAttempts,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return f(composer);
  }
}

class $$LocalSalesTableOrderingComposer
    extends Composer<_$PosDatabase, $LocalSalesTable> {
  $$LocalSalesTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get operationId => $composableBuilder(
    column: $table.operationId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get contextId => $composableBuilder(
    column: $table.contextId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get sequence => $composableBuilder(
    column: $table.sequence,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get localFolio => $composableBuilder(
    column: $table.localFolio,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get serverFolio => $composableBuilder(
    column: $table.serverFolio,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get customerId => $composableBuilder(
    column: $table.customerId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get customerName => $composableBuilder(
    column: $table.customerName,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get branchName => $composableBuilder(
    column: $table.branchName,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get operatorName => $composableBuilder(
    column: $table.operatorName,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get saleType => $composableBuilder(
    column: $table.saleType,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get discountBasisPoints => $composableBuilder(
    column: $table.discountBasisPoints,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get totalCents => $composableBuilder(
    column: $table.totalCents,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get appliedCents => $composableBuilder(
    column: $table.appliedCents,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get receivedCents => $composableBuilder(
    column: $table.receivedCents,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get changeCents => $composableBuilder(
    column: $table.changeCents,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get balanceCents => $composableBuilder(
    column: $table.balanceCents,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<bool> get paid => $composableBuilder(
    column: $table.paid,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get occurredAt => $composableBuilder(
    column: $table.occurredAt,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get offlineLeaseId => $composableBuilder(
    column: $table.offlineLeaseId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get catalogRevision => $composableBuilder(
    column: $table.catalogRevision,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get requestHash => $composableBuilder(
    column: $table.requestHash,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$LocalSalesTableAnnotationComposer
    extends Composer<_$PosDatabase, $LocalSalesTable> {
  $$LocalSalesTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<String> get operationId => $composableBuilder(
    column: $table.operationId,
    builder: (column) => column,
  );

  GeneratedColumn<String> get contextId =>
      $composableBuilder(column: $table.contextId, builder: (column) => column);

  GeneratedColumn<int> get sequence =>
      $composableBuilder(column: $table.sequence, builder: (column) => column);

  GeneratedColumn<String> get localFolio => $composableBuilder(
    column: $table.localFolio,
    builder: (column) => column,
  );

  GeneratedColumn<String> get serverFolio => $composableBuilder(
    column: $table.serverFolio,
    builder: (column) => column,
  );

  GeneratedColumn<String> get customerId => $composableBuilder(
    column: $table.customerId,
    builder: (column) => column,
  );

  GeneratedColumn<String> get customerName => $composableBuilder(
    column: $table.customerName,
    builder: (column) => column,
  );

  GeneratedColumn<String> get branchName => $composableBuilder(
    column: $table.branchName,
    builder: (column) => column,
  );

  GeneratedColumn<String> get operatorName => $composableBuilder(
    column: $table.operatorName,
    builder: (column) => column,
  );

  GeneratedColumn<String> get saleType =>
      $composableBuilder(column: $table.saleType, builder: (column) => column);

  GeneratedColumn<int> get discountBasisPoints => $composableBuilder(
    column: $table.discountBasisPoints,
    builder: (column) => column,
  );

  GeneratedColumn<int> get totalCents => $composableBuilder(
    column: $table.totalCents,
    builder: (column) => column,
  );

  GeneratedColumn<int> get appliedCents => $composableBuilder(
    column: $table.appliedCents,
    builder: (column) => column,
  );

  GeneratedColumn<int> get receivedCents => $composableBuilder(
    column: $table.receivedCents,
    builder: (column) => column,
  );

  GeneratedColumn<int> get changeCents => $composableBuilder(
    column: $table.changeCents,
    builder: (column) => column,
  );

  GeneratedColumn<int> get balanceCents => $composableBuilder(
    column: $table.balanceCents,
    builder: (column) => column,
  );

  GeneratedColumn<bool> get paid =>
      $composableBuilder(column: $table.paid, builder: (column) => column);

  GeneratedColumn<String> get status =>
      $composableBuilder(column: $table.status, builder: (column) => column);

  GeneratedColumn<String> get occurredAt => $composableBuilder(
    column: $table.occurredAt,
    builder: (column) => column,
  );

  GeneratedColumn<String> get offlineLeaseId => $composableBuilder(
    column: $table.offlineLeaseId,
    builder: (column) => column,
  );

  GeneratedColumn<String> get catalogRevision => $composableBuilder(
    column: $table.catalogRevision,
    builder: (column) => column,
  );

  GeneratedColumn<String> get requestHash => $composableBuilder(
    column: $table.requestHash,
    builder: (column) => column,
  );

  Expression<T> localSaleItemsRefs<T extends Object>(
    Expression<T> Function($$LocalSaleItemsTableAnnotationComposer a) f,
  ) {
    final $$LocalSaleItemsTableAnnotationComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.id,
      referencedTable: $db.localSaleItems,
      getReferencedColumn: (t) => t.saleId,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$LocalSaleItemsTableAnnotationComposer(
            $db: $db,
            $table: $db.localSaleItems,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return f(composer);
  }

  Expression<T> localPaymentsRefs<T extends Object>(
    Expression<T> Function($$LocalPaymentsTableAnnotationComposer a) f,
  ) {
    final $$LocalPaymentsTableAnnotationComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.id,
      referencedTable: $db.localPayments,
      getReferencedColumn: (t) => t.saleId,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$LocalPaymentsTableAnnotationComposer(
            $db: $db,
            $table: $db.localPayments,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return f(composer);
  }

  Expression<T> outboxRefs<T extends Object>(
    Expression<T> Function($$OutboxTableAnnotationComposer a) f,
  ) {
    final $$OutboxTableAnnotationComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.id,
      referencedTable: $db.outbox,
      getReferencedColumn: (t) => t.saleId,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$OutboxTableAnnotationComposer(
            $db: $db,
            $table: $db.outbox,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return f(composer);
  }

  Expression<T> receiptAttemptsRefs<T extends Object>(
    Expression<T> Function($$ReceiptAttemptsTableAnnotationComposer a) f,
  ) {
    final $$ReceiptAttemptsTableAnnotationComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.id,
      referencedTable: $db.receiptAttempts,
      getReferencedColumn: (t) => t.saleId,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$ReceiptAttemptsTableAnnotationComposer(
            $db: $db,
            $table: $db.receiptAttempts,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return f(composer);
  }
}

class $$LocalSalesTableTableManager
    extends
        RootTableManager<
          _$PosDatabase,
          $LocalSalesTable,
          LocalSale,
          $$LocalSalesTableFilterComposer,
          $$LocalSalesTableOrderingComposer,
          $$LocalSalesTableAnnotationComposer,
          $$LocalSalesTableCreateCompanionBuilder,
          $$LocalSalesTableUpdateCompanionBuilder,
          (LocalSale, $$LocalSalesTableReferences),
          LocalSale,
          PrefetchHooks Function({
            bool localSaleItemsRefs,
            bool localPaymentsRefs,
            bool outboxRefs,
            bool receiptAttemptsRefs,
          })
        > {
  $$LocalSalesTableTableManager(_$PosDatabase db, $LocalSalesTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$LocalSalesTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$LocalSalesTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$LocalSalesTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> id = const Value.absent(),
                Value<String> operationId = const Value.absent(),
                Value<String> contextId = const Value.absent(),
                Value<int> sequence = const Value.absent(),
                Value<String> localFolio = const Value.absent(),
                Value<String?> serverFolio = const Value.absent(),
                Value<String> customerId = const Value.absent(),
                Value<String> customerName = const Value.absent(),
                Value<String> branchName = const Value.absent(),
                Value<String> operatorName = const Value.absent(),
                Value<String> saleType = const Value.absent(),
                Value<int> discountBasisPoints = const Value.absent(),
                Value<int> totalCents = const Value.absent(),
                Value<int> appliedCents = const Value.absent(),
                Value<int> receivedCents = const Value.absent(),
                Value<int> changeCents = const Value.absent(),
                Value<int> balanceCents = const Value.absent(),
                Value<bool> paid = const Value.absent(),
                Value<String> status = const Value.absent(),
                Value<String> occurredAt = const Value.absent(),
                Value<String> offlineLeaseId = const Value.absent(),
                Value<String> catalogRevision = const Value.absent(),
                Value<String> requestHash = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => LocalSalesCompanion(
                id: id,
                operationId: operationId,
                contextId: contextId,
                sequence: sequence,
                localFolio: localFolio,
                serverFolio: serverFolio,
                customerId: customerId,
                customerName: customerName,
                branchName: branchName,
                operatorName: operatorName,
                saleType: saleType,
                discountBasisPoints: discountBasisPoints,
                totalCents: totalCents,
                appliedCents: appliedCents,
                receivedCents: receivedCents,
                changeCents: changeCents,
                balanceCents: balanceCents,
                paid: paid,
                status: status,
                occurredAt: occurredAt,
                offlineLeaseId: offlineLeaseId,
                catalogRevision: catalogRevision,
                requestHash: requestHash,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String id,
                required String operationId,
                required String contextId,
                required int sequence,
                required String localFolio,
                Value<String?> serverFolio = const Value.absent(),
                required String customerId,
                required String customerName,
                required String branchName,
                required String operatorName,
                required String saleType,
                required int discountBasisPoints,
                required int totalCents,
                required int appliedCents,
                required int receivedCents,
                required int changeCents,
                required int balanceCents,
                required bool paid,
                Value<String> status = const Value.absent(),
                required String occurredAt,
                required String offlineLeaseId,
                required String catalogRevision,
                required String requestHash,
                Value<int> rowid = const Value.absent(),
              }) => LocalSalesCompanion.insert(
                id: id,
                operationId: operationId,
                contextId: contextId,
                sequence: sequence,
                localFolio: localFolio,
                serverFolio: serverFolio,
                customerId: customerId,
                customerName: customerName,
                branchName: branchName,
                operatorName: operatorName,
                saleType: saleType,
                discountBasisPoints: discountBasisPoints,
                totalCents: totalCents,
                appliedCents: appliedCents,
                receivedCents: receivedCents,
                changeCents: changeCents,
                balanceCents: balanceCents,
                paid: paid,
                status: status,
                occurredAt: occurredAt,
                offlineLeaseId: offlineLeaseId,
                catalogRevision: catalogRevision,
                requestHash: requestHash,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$LocalSalesTable, LocalSale>(table),
                  $$LocalSalesTableReferences(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback:
              ({
                localSaleItemsRefs = false,
                localPaymentsRefs = false,
                outboxRefs = false,
                receiptAttemptsRefs = false,
              }) {
                return PrefetchHooks(
                  db: db,
                  explicitlyWatchedTables: [
                    if (localSaleItemsRefs) db.localSaleItems,
                    if (localPaymentsRefs) db.localPayments,
                    if (outboxRefs) db.outbox,
                    if (receiptAttemptsRefs) db.receiptAttempts,
                  ],
                  addJoins: null,
                  getPrefetchedDataCallback: (items) async {
                    return [
                      if (localSaleItemsRefs)
                        await $_getPrefetchedData<
                          LocalSale,
                          $LocalSalesTable,
                          LocalSaleItem
                        >(
                          currentTable: table,
                          referencedTable: $$LocalSalesTableReferences
                              ._localSaleItemsRefsTable(db),
                          managerFromTypedResult: (p0) =>
                              $$LocalSalesTableReferences(
                                db,
                                table,
                                p0,
                              ).localSaleItemsRefs,
                          referencedItemsForCurrentItem:
                              (item, referencedItems) => referencedItems.where(
                                (e) => e.saleId == item.id,
                              ),
                          typedResults: items,
                        ),
                      if (localPaymentsRefs)
                        await $_getPrefetchedData<
                          LocalSale,
                          $LocalSalesTable,
                          LocalPayment
                        >(
                          currentTable: table,
                          referencedTable: $$LocalSalesTableReferences
                              ._localPaymentsRefsTable(db),
                          managerFromTypedResult: (p0) =>
                              $$LocalSalesTableReferences(
                                db,
                                table,
                                p0,
                              ).localPaymentsRefs,
                          referencedItemsForCurrentItem:
                              (item, referencedItems) => referencedItems.where(
                                (e) => e.saleId == item.id,
                              ),
                          typedResults: items,
                        ),
                      if (outboxRefs)
                        await $_getPrefetchedData<
                          LocalSale,
                          $LocalSalesTable,
                          OutboxData
                        >(
                          currentTable: table,
                          referencedTable: $$LocalSalesTableReferences
                              ._outboxRefsTable(db),
                          managerFromTypedResult: (p0) =>
                              $$LocalSalesTableReferences(
                                db,
                                table,
                                p0,
                              ).outboxRefs,
                          referencedItemsForCurrentItem:
                              (item, referencedItems) => referencedItems.where(
                                (e) => e.saleId == item.id,
                              ),
                          typedResults: items,
                        ),
                      if (receiptAttemptsRefs)
                        await $_getPrefetchedData<
                          LocalSale,
                          $LocalSalesTable,
                          ReceiptAttempt
                        >(
                          currentTable: table,
                          referencedTable: $$LocalSalesTableReferences
                              ._receiptAttemptsRefsTable(db),
                          managerFromTypedResult: (p0) =>
                              $$LocalSalesTableReferences(
                                db,
                                table,
                                p0,
                              ).receiptAttemptsRefs,
                          referencedItemsForCurrentItem:
                              (item, referencedItems) => referencedItems.where(
                                (e) => e.saleId == item.id,
                              ),
                          typedResults: items,
                        ),
                    ];
                  },
                );
              },
        ),
      );
}

typedef $$LocalSalesTableProcessedTableManager =
    ProcessedTableManager<
      _$PosDatabase,
      $LocalSalesTable,
      LocalSale,
      $$LocalSalesTableFilterComposer,
      $$LocalSalesTableOrderingComposer,
      $$LocalSalesTableAnnotationComposer,
      $$LocalSalesTableCreateCompanionBuilder,
      $$LocalSalesTableUpdateCompanionBuilder,
      (LocalSale, $$LocalSalesTableReferences),
      LocalSale,
      PrefetchHooks Function({
        bool localSaleItemsRefs,
        bool localPaymentsRefs,
        bool outboxRefs,
        bool receiptAttemptsRefs,
      })
    >;
typedef $$LocalSaleItemsTableCreateCompanionBuilder =
    LocalSaleItemsCompanion Function({
      required String id,
      required String saleId,
      required String productId,
      required String productName,
      required String barcode,
      required int quantityUnits,
      required int originalPriceCents,
      required int unitPriceCents,
      required int totalCents,
      required int ordinal,
      Value<int> rowid,
    });
typedef $$LocalSaleItemsTableUpdateCompanionBuilder =
    LocalSaleItemsCompanion Function({
      Value<String> id,
      Value<String> saleId,
      Value<String> productId,
      Value<String> productName,
      Value<String> barcode,
      Value<int> quantityUnits,
      Value<int> originalPriceCents,
      Value<int> unitPriceCents,
      Value<int> totalCents,
      Value<int> ordinal,
      Value<int> rowid,
    });

final class $$LocalSaleItemsTableReferences
    extends BaseReferences<_$PosDatabase, $LocalSaleItemsTable, LocalSaleItem> {
  $$LocalSaleItemsTableReferences(
    super.$_db,
    super.$_table,
    super.$_typedResult,
  );

  static $LocalSalesTable _saleIdTable(_$PosDatabase db) =>
      db.localSales.createAlias('local_sale_items__sale_id__local_sales__id');

  $$LocalSalesTableProcessedTableManager get saleId {
    final $_column = $_itemColumn<String>('sale_id')!;

    final manager = $$LocalSalesTableTableManager(
      $_db,
      $_db.localSales,
    ).filter((f) => f.id.sqlEquals($_column));
    final item = $_typedResult.readTableOrNull(_saleIdTable($_db));
    if (item == null) return manager;
    return ProcessedTableManager(
      manager.$state.copyWith(prefetchedData: [item]),
    );
  }
}

class $$LocalSaleItemsTableFilterComposer
    extends Composer<_$PosDatabase, $LocalSaleItemsTable> {
  $$LocalSaleItemsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get productId => $composableBuilder(
    column: $table.productId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get productName => $composableBuilder(
    column: $table.productName,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get barcode => $composableBuilder(
    column: $table.barcode,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get quantityUnits => $composableBuilder(
    column: $table.quantityUnits,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get originalPriceCents => $composableBuilder(
    column: $table.originalPriceCents,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get unitPriceCents => $composableBuilder(
    column: $table.unitPriceCents,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get totalCents => $composableBuilder(
    column: $table.totalCents,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get ordinal => $composableBuilder(
    column: $table.ordinal,
    builder: (column) => ColumnFilters(column),
  );

  $$LocalSalesTableFilterComposer get saleId {
    final $$LocalSalesTableFilterComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.saleId,
      referencedTable: $db.localSales,
      getReferencedColumn: (t) => t.id,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$LocalSalesTableFilterComposer(
            $db: $db,
            $table: $db.localSales,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return composer;
  }
}

class $$LocalSaleItemsTableOrderingComposer
    extends Composer<_$PosDatabase, $LocalSaleItemsTable> {
  $$LocalSaleItemsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get productId => $composableBuilder(
    column: $table.productId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get productName => $composableBuilder(
    column: $table.productName,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get barcode => $composableBuilder(
    column: $table.barcode,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get quantityUnits => $composableBuilder(
    column: $table.quantityUnits,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get originalPriceCents => $composableBuilder(
    column: $table.originalPriceCents,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get unitPriceCents => $composableBuilder(
    column: $table.unitPriceCents,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get totalCents => $composableBuilder(
    column: $table.totalCents,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get ordinal => $composableBuilder(
    column: $table.ordinal,
    builder: (column) => ColumnOrderings(column),
  );

  $$LocalSalesTableOrderingComposer get saleId {
    final $$LocalSalesTableOrderingComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.saleId,
      referencedTable: $db.localSales,
      getReferencedColumn: (t) => t.id,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$LocalSalesTableOrderingComposer(
            $db: $db,
            $table: $db.localSales,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return composer;
  }
}

class $$LocalSaleItemsTableAnnotationComposer
    extends Composer<_$PosDatabase, $LocalSaleItemsTable> {
  $$LocalSaleItemsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<String> get productId =>
      $composableBuilder(column: $table.productId, builder: (column) => column);

  GeneratedColumn<String> get productName => $composableBuilder(
    column: $table.productName,
    builder: (column) => column,
  );

  GeneratedColumn<String> get barcode =>
      $composableBuilder(column: $table.barcode, builder: (column) => column);

  GeneratedColumn<int> get quantityUnits => $composableBuilder(
    column: $table.quantityUnits,
    builder: (column) => column,
  );

  GeneratedColumn<int> get originalPriceCents => $composableBuilder(
    column: $table.originalPriceCents,
    builder: (column) => column,
  );

  GeneratedColumn<int> get unitPriceCents => $composableBuilder(
    column: $table.unitPriceCents,
    builder: (column) => column,
  );

  GeneratedColumn<int> get totalCents => $composableBuilder(
    column: $table.totalCents,
    builder: (column) => column,
  );

  GeneratedColumn<int> get ordinal =>
      $composableBuilder(column: $table.ordinal, builder: (column) => column);

  $$LocalSalesTableAnnotationComposer get saleId {
    final $$LocalSalesTableAnnotationComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.saleId,
      referencedTable: $db.localSales,
      getReferencedColumn: (t) => t.id,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$LocalSalesTableAnnotationComposer(
            $db: $db,
            $table: $db.localSales,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return composer;
  }
}

class $$LocalSaleItemsTableTableManager
    extends
        RootTableManager<
          _$PosDatabase,
          $LocalSaleItemsTable,
          LocalSaleItem,
          $$LocalSaleItemsTableFilterComposer,
          $$LocalSaleItemsTableOrderingComposer,
          $$LocalSaleItemsTableAnnotationComposer,
          $$LocalSaleItemsTableCreateCompanionBuilder,
          $$LocalSaleItemsTableUpdateCompanionBuilder,
          (LocalSaleItem, $$LocalSaleItemsTableReferences),
          LocalSaleItem,
          PrefetchHooks Function({bool saleId})
        > {
  $$LocalSaleItemsTableTableManager(
    _$PosDatabase db,
    $LocalSaleItemsTable table,
  ) : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$LocalSaleItemsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$LocalSaleItemsTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$LocalSaleItemsTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> id = const Value.absent(),
                Value<String> saleId = const Value.absent(),
                Value<String> productId = const Value.absent(),
                Value<String> productName = const Value.absent(),
                Value<String> barcode = const Value.absent(),
                Value<int> quantityUnits = const Value.absent(),
                Value<int> originalPriceCents = const Value.absent(),
                Value<int> unitPriceCents = const Value.absent(),
                Value<int> totalCents = const Value.absent(),
                Value<int> ordinal = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => LocalSaleItemsCompanion(
                id: id,
                saleId: saleId,
                productId: productId,
                productName: productName,
                barcode: barcode,
                quantityUnits: quantityUnits,
                originalPriceCents: originalPriceCents,
                unitPriceCents: unitPriceCents,
                totalCents: totalCents,
                ordinal: ordinal,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String id,
                required String saleId,
                required String productId,
                required String productName,
                required String barcode,
                required int quantityUnits,
                required int originalPriceCents,
                required int unitPriceCents,
                required int totalCents,
                required int ordinal,
                Value<int> rowid = const Value.absent(),
              }) => LocalSaleItemsCompanion.insert(
                id: id,
                saleId: saleId,
                productId: productId,
                productName: productName,
                barcode: barcode,
                quantityUnits: quantityUnits,
                originalPriceCents: originalPriceCents,
                unitPriceCents: unitPriceCents,
                totalCents: totalCents,
                ordinal: ordinal,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$LocalSaleItemsTable, LocalSaleItem>(table),
                  $$LocalSaleItemsTableReferences(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback: ({saleId = false}) {
            return PrefetchHooks(
              db: db,
              explicitlyWatchedTables: [],
              addJoins:
                  <
                    T extends TableManagerState<
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic
                    >
                  >(state) {
                    if (saleId) {
                      state = state.withJoin(
                        currentTable: table,
                        currentColumn: table.saleId,
                        referencedTable: $$LocalSaleItemsTableReferences
                            ._saleIdTable(db),
                        referencedColumn: $$LocalSaleItemsTableReferences
                            ._saleIdTable(db)
                            .id,
                      ) as T;
                    }

                    return state;
                  },
              getPrefetchedDataCallback: (items) async {
                return [];
              },
            );
          },
        ),
      );
}

typedef $$LocalSaleItemsTableProcessedTableManager =
    ProcessedTableManager<
      _$PosDatabase,
      $LocalSaleItemsTable,
      LocalSaleItem,
      $$LocalSaleItemsTableFilterComposer,
      $$LocalSaleItemsTableOrderingComposer,
      $$LocalSaleItemsTableAnnotationComposer,
      $$LocalSaleItemsTableCreateCompanionBuilder,
      $$LocalSaleItemsTableUpdateCompanionBuilder,
      (LocalSaleItem, $$LocalSaleItemsTableReferences),
      LocalSaleItem,
      PrefetchHooks Function({bool saleId})
    >;
typedef $$LocalPaymentsTableCreateCompanionBuilder =
    LocalPaymentsCompanion Function({
      required String id,
      required String saleId,
      Value<String> method,
      required int appliedCents,
      required int receivedCents,
      required int changeCents,
      Value<int> rowid,
    });
typedef $$LocalPaymentsTableUpdateCompanionBuilder =
    LocalPaymentsCompanion Function({
      Value<String> id,
      Value<String> saleId,
      Value<String> method,
      Value<int> appliedCents,
      Value<int> receivedCents,
      Value<int> changeCents,
      Value<int> rowid,
    });

final class $$LocalPaymentsTableReferences
    extends BaseReferences<_$PosDatabase, $LocalPaymentsTable, LocalPayment> {
  $$LocalPaymentsTableReferences(
    super.$_db,
    super.$_table,
    super.$_typedResult,
  );

  static $LocalSalesTable _saleIdTable(_$PosDatabase db) =>
      db.localSales.createAlias('local_payments__sale_id__local_sales__id');

  $$LocalSalesTableProcessedTableManager get saleId {
    final $_column = $_itemColumn<String>('sale_id')!;

    final manager = $$LocalSalesTableTableManager(
      $_db,
      $_db.localSales,
    ).filter((f) => f.id.sqlEquals($_column));
    final item = $_typedResult.readTableOrNull(_saleIdTable($_db));
    if (item == null) return manager;
    return ProcessedTableManager(
      manager.$state.copyWith(prefetchedData: [item]),
    );
  }
}

class $$LocalPaymentsTableFilterComposer
    extends Composer<_$PosDatabase, $LocalPaymentsTable> {
  $$LocalPaymentsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get method => $composableBuilder(
    column: $table.method,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get appliedCents => $composableBuilder(
    column: $table.appliedCents,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get receivedCents => $composableBuilder(
    column: $table.receivedCents,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get changeCents => $composableBuilder(
    column: $table.changeCents,
    builder: (column) => ColumnFilters(column),
  );

  $$LocalSalesTableFilterComposer get saleId {
    final $$LocalSalesTableFilterComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.saleId,
      referencedTable: $db.localSales,
      getReferencedColumn: (t) => t.id,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$LocalSalesTableFilterComposer(
            $db: $db,
            $table: $db.localSales,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return composer;
  }
}

class $$LocalPaymentsTableOrderingComposer
    extends Composer<_$PosDatabase, $LocalPaymentsTable> {
  $$LocalPaymentsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get method => $composableBuilder(
    column: $table.method,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get appliedCents => $composableBuilder(
    column: $table.appliedCents,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get receivedCents => $composableBuilder(
    column: $table.receivedCents,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get changeCents => $composableBuilder(
    column: $table.changeCents,
    builder: (column) => ColumnOrderings(column),
  );

  $$LocalSalesTableOrderingComposer get saleId {
    final $$LocalSalesTableOrderingComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.saleId,
      referencedTable: $db.localSales,
      getReferencedColumn: (t) => t.id,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$LocalSalesTableOrderingComposer(
            $db: $db,
            $table: $db.localSales,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return composer;
  }
}

class $$LocalPaymentsTableAnnotationComposer
    extends Composer<_$PosDatabase, $LocalPaymentsTable> {
  $$LocalPaymentsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<String> get method =>
      $composableBuilder(column: $table.method, builder: (column) => column);

  GeneratedColumn<int> get appliedCents => $composableBuilder(
    column: $table.appliedCents,
    builder: (column) => column,
  );

  GeneratedColumn<int> get receivedCents => $composableBuilder(
    column: $table.receivedCents,
    builder: (column) => column,
  );

  GeneratedColumn<int> get changeCents => $composableBuilder(
    column: $table.changeCents,
    builder: (column) => column,
  );

  $$LocalSalesTableAnnotationComposer get saleId {
    final $$LocalSalesTableAnnotationComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.saleId,
      referencedTable: $db.localSales,
      getReferencedColumn: (t) => t.id,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$LocalSalesTableAnnotationComposer(
            $db: $db,
            $table: $db.localSales,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return composer;
  }
}

class $$LocalPaymentsTableTableManager
    extends
        RootTableManager<
          _$PosDatabase,
          $LocalPaymentsTable,
          LocalPayment,
          $$LocalPaymentsTableFilterComposer,
          $$LocalPaymentsTableOrderingComposer,
          $$LocalPaymentsTableAnnotationComposer,
          $$LocalPaymentsTableCreateCompanionBuilder,
          $$LocalPaymentsTableUpdateCompanionBuilder,
          (LocalPayment, $$LocalPaymentsTableReferences),
          LocalPayment,
          PrefetchHooks Function({bool saleId})
        > {
  $$LocalPaymentsTableTableManager(_$PosDatabase db, $LocalPaymentsTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$LocalPaymentsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$LocalPaymentsTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$LocalPaymentsTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> id = const Value.absent(),
                Value<String> saleId = const Value.absent(),
                Value<String> method = const Value.absent(),
                Value<int> appliedCents = const Value.absent(),
                Value<int> receivedCents = const Value.absent(),
                Value<int> changeCents = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => LocalPaymentsCompanion(
                id: id,
                saleId: saleId,
                method: method,
                appliedCents: appliedCents,
                receivedCents: receivedCents,
                changeCents: changeCents,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String id,
                required String saleId,
                Value<String> method = const Value.absent(),
                required int appliedCents,
                required int receivedCents,
                required int changeCents,
                Value<int> rowid = const Value.absent(),
              }) => LocalPaymentsCompanion.insert(
                id: id,
                saleId: saleId,
                method: method,
                appliedCents: appliedCents,
                receivedCents: receivedCents,
                changeCents: changeCents,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$LocalPaymentsTable, LocalPayment>(table),
                  $$LocalPaymentsTableReferences(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback: ({saleId = false}) {
            return PrefetchHooks(
              db: db,
              explicitlyWatchedTables: [],
              addJoins:
                  <
                    T extends TableManagerState<
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic
                    >
                  >(state) {
                    if (saleId) {
                      state = state.withJoin(
                        currentTable: table,
                        currentColumn: table.saleId,
                        referencedTable: $$LocalPaymentsTableReferences
                            ._saleIdTable(db),
                        referencedColumn: $$LocalPaymentsTableReferences
                            ._saleIdTable(db)
                            .id,
                      ) as T;
                    }

                    return state;
                  },
              getPrefetchedDataCallback: (items) async {
                return [];
              },
            );
          },
        ),
      );
}

typedef $$LocalPaymentsTableProcessedTableManager =
    ProcessedTableManager<
      _$PosDatabase,
      $LocalPaymentsTable,
      LocalPayment,
      $$LocalPaymentsTableFilterComposer,
      $$LocalPaymentsTableOrderingComposer,
      $$LocalPaymentsTableAnnotationComposer,
      $$LocalPaymentsTableCreateCompanionBuilder,
      $$LocalPaymentsTableUpdateCompanionBuilder,
      (LocalPayment, $$LocalPaymentsTableReferences),
      LocalPayment,
      PrefetchHooks Function({bool saleId})
    >;
typedef $$OutboxTableCreateCompanionBuilder = OutboxCompanion Function({
  required String operationId,
  required String saleId,
  required String contextId,
  required int sequence,
  required String payload,
  required String payloadHash,
  Value<String> status,
  Value<int> attempts,
  Value<String?> nextAttemptAt,
  Value<String?> workerLease,
  Value<String?> serverResult,
  Value<String?> errorCode,
  Value<String?> error,
  Value<int> rowid,
});
typedef $$OutboxTableUpdateCompanionBuilder = OutboxCompanion Function({
  Value<String> operationId,
  Value<String> saleId,
  Value<String> contextId,
  Value<int> sequence,
  Value<String> payload,
  Value<String> payloadHash,
  Value<String> status,
  Value<int> attempts,
  Value<String?> nextAttemptAt,
  Value<String?> workerLease,
  Value<String?> serverResult,
  Value<String?> errorCode,
  Value<String?> error,
  Value<int> rowid,
});

final class $$OutboxTableReferences
    extends BaseReferences<_$PosDatabase, $OutboxTable, OutboxData> {
  $$OutboxTableReferences(super.$_db, super.$_table, super.$_typedResult);

  static $LocalSalesTable _saleIdTable(_$PosDatabase db) =>
      db.localSales.createAlias('outbox__sale_id__local_sales__id');

  $$LocalSalesTableProcessedTableManager get saleId {
    final $_column = $_itemColumn<String>('sale_id')!;

    final manager = $$LocalSalesTableTableManager(
      $_db,
      $_db.localSales,
    ).filter((f) => f.id.sqlEquals($_column));
    final item = $_typedResult.readTableOrNull(_saleIdTable($_db));
    if (item == null) return manager;
    return ProcessedTableManager(
      manager.$state.copyWith(prefetchedData: [item]),
    );
  }
}

class $$OutboxTableFilterComposer
    extends Composer<_$PosDatabase, $OutboxTable> {
  $$OutboxTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get operationId => $composableBuilder(
    column: $table.operationId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get contextId => $composableBuilder(
    column: $table.contextId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get sequence => $composableBuilder(
    column: $table.sequence,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get payload => $composableBuilder(
    column: $table.payload,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get payloadHash => $composableBuilder(
    column: $table.payloadHash,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get attempts => $composableBuilder(
    column: $table.attempts,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get nextAttemptAt => $composableBuilder(
    column: $table.nextAttemptAt,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get workerLease => $composableBuilder(
    column: $table.workerLease,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get serverResult => $composableBuilder(
    column: $table.serverResult,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get errorCode => $composableBuilder(
    column: $table.errorCode,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get error => $composableBuilder(
    column: $table.error,
    builder: (column) => ColumnFilters(column),
  );

  $$LocalSalesTableFilterComposer get saleId {
    final $$LocalSalesTableFilterComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.saleId,
      referencedTable: $db.localSales,
      getReferencedColumn: (t) => t.id,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$LocalSalesTableFilterComposer(
            $db: $db,
            $table: $db.localSales,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return composer;
  }
}

class $$OutboxTableOrderingComposer
    extends Composer<_$PosDatabase, $OutboxTable> {
  $$OutboxTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get operationId => $composableBuilder(
    column: $table.operationId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get contextId => $composableBuilder(
    column: $table.contextId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get sequence => $composableBuilder(
    column: $table.sequence,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get payload => $composableBuilder(
    column: $table.payload,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get payloadHash => $composableBuilder(
    column: $table.payloadHash,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get attempts => $composableBuilder(
    column: $table.attempts,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get nextAttemptAt => $composableBuilder(
    column: $table.nextAttemptAt,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get workerLease => $composableBuilder(
    column: $table.workerLease,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get serverResult => $composableBuilder(
    column: $table.serverResult,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get errorCode => $composableBuilder(
    column: $table.errorCode,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get error => $composableBuilder(
    column: $table.error,
    builder: (column) => ColumnOrderings(column),
  );

  $$LocalSalesTableOrderingComposer get saleId {
    final $$LocalSalesTableOrderingComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.saleId,
      referencedTable: $db.localSales,
      getReferencedColumn: (t) => t.id,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$LocalSalesTableOrderingComposer(
            $db: $db,
            $table: $db.localSales,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return composer;
  }
}

class $$OutboxTableAnnotationComposer
    extends Composer<_$PosDatabase, $OutboxTable> {
  $$OutboxTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get operationId => $composableBuilder(
    column: $table.operationId,
    builder: (column) => column,
  );

  GeneratedColumn<String> get contextId =>
      $composableBuilder(column: $table.contextId, builder: (column) => column);

  GeneratedColumn<int> get sequence =>
      $composableBuilder(column: $table.sequence, builder: (column) => column);

  GeneratedColumn<String> get payload =>
      $composableBuilder(column: $table.payload, builder: (column) => column);

  GeneratedColumn<String> get payloadHash => $composableBuilder(
    column: $table.payloadHash,
    builder: (column) => column,
  );

  GeneratedColumn<String> get status =>
      $composableBuilder(column: $table.status, builder: (column) => column);

  GeneratedColumn<int> get attempts =>
      $composableBuilder(column: $table.attempts, builder: (column) => column);

  GeneratedColumn<String> get nextAttemptAt => $composableBuilder(
    column: $table.nextAttemptAt,
    builder: (column) => column,
  );

  GeneratedColumn<String> get workerLease => $composableBuilder(
    column: $table.workerLease,
    builder: (column) => column,
  );

  GeneratedColumn<String> get serverResult => $composableBuilder(
    column: $table.serverResult,
    builder: (column) => column,
  );

  GeneratedColumn<String> get errorCode =>
      $composableBuilder(column: $table.errorCode, builder: (column) => column);

  GeneratedColumn<String> get error =>
      $composableBuilder(column: $table.error, builder: (column) => column);

  $$LocalSalesTableAnnotationComposer get saleId {
    final $$LocalSalesTableAnnotationComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.saleId,
      referencedTable: $db.localSales,
      getReferencedColumn: (t) => t.id,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$LocalSalesTableAnnotationComposer(
            $db: $db,
            $table: $db.localSales,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return composer;
  }
}

class $$OutboxTableTableManager
    extends
        RootTableManager<
          _$PosDatabase,
          $OutboxTable,
          OutboxData,
          $$OutboxTableFilterComposer,
          $$OutboxTableOrderingComposer,
          $$OutboxTableAnnotationComposer,
          $$OutboxTableCreateCompanionBuilder,
          $$OutboxTableUpdateCompanionBuilder,
          (OutboxData, $$OutboxTableReferences),
          OutboxData,
          PrefetchHooks Function({bool saleId})
        > {
  $$OutboxTableTableManager(_$PosDatabase db, $OutboxTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$OutboxTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$OutboxTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$OutboxTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> operationId = const Value.absent(),
                Value<String> saleId = const Value.absent(),
                Value<String> contextId = const Value.absent(),
                Value<int> sequence = const Value.absent(),
                Value<String> payload = const Value.absent(),
                Value<String> payloadHash = const Value.absent(),
                Value<String> status = const Value.absent(),
                Value<int> attempts = const Value.absent(),
                Value<String?> nextAttemptAt = const Value.absent(),
                Value<String?> workerLease = const Value.absent(),
                Value<String?> serverResult = const Value.absent(),
                Value<String?> errorCode = const Value.absent(),
                Value<String?> error = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => OutboxCompanion(
                operationId: operationId,
                saleId: saleId,
                contextId: contextId,
                sequence: sequence,
                payload: payload,
                payloadHash: payloadHash,
                status: status,
                attempts: attempts,
                nextAttemptAt: nextAttemptAt,
                workerLease: workerLease,
                serverResult: serverResult,
                errorCode: errorCode,
                error: error,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String operationId,
                required String saleId,
                required String contextId,
                required int sequence,
                required String payload,
                required String payloadHash,
                Value<String> status = const Value.absent(),
                Value<int> attempts = const Value.absent(),
                Value<String?> nextAttemptAt = const Value.absent(),
                Value<String?> workerLease = const Value.absent(),
                Value<String?> serverResult = const Value.absent(),
                Value<String?> errorCode = const Value.absent(),
                Value<String?> error = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => OutboxCompanion.insert(
                operationId: operationId,
                saleId: saleId,
                contextId: contextId,
                sequence: sequence,
                payload: payload,
                payloadHash: payloadHash,
                status: status,
                attempts: attempts,
                nextAttemptAt: nextAttemptAt,
                workerLease: workerLease,
                serverResult: serverResult,
                errorCode: errorCode,
                error: error,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$OutboxTable, OutboxData>(table),
                  $$OutboxTableReferences(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback: ({saleId = false}) {
            return PrefetchHooks(
              db: db,
              explicitlyWatchedTables: [],
              addJoins:
                  <
                    T extends TableManagerState<
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic
                    >
                  >(state) {
                    if (saleId) {
                      state = state.withJoin(
                        currentTable: table,
                        currentColumn: table.saleId,
                        referencedTable: $$OutboxTableReferences._saleIdTable(
                          db,
                        ),
                        referencedColumn: $$OutboxTableReferences
                            ._saleIdTable(db)
                            .id,
                      ) as T;
                    }

                    return state;
                  },
              getPrefetchedDataCallback: (items) async {
                return [];
              },
            );
          },
        ),
      );
}

typedef $$OutboxTableProcessedTableManager =
    ProcessedTableManager<
      _$PosDatabase,
      $OutboxTable,
      OutboxData,
      $$OutboxTableFilterComposer,
      $$OutboxTableOrderingComposer,
      $$OutboxTableAnnotationComposer,
      $$OutboxTableCreateCompanionBuilder,
      $$OutboxTableUpdateCompanionBuilder,
      (OutboxData, $$OutboxTableReferences),
      OutboxData,
      PrefetchHooks Function({bool saleId})
    >;
typedef $$DeviceSequenceTableCreateCompanionBuilder =
    DeviceSequenceCompanion Function({
      required String contextId,
      Value<int> nextSequence,
      Value<int> rowid,
    });
typedef $$DeviceSequenceTableUpdateCompanionBuilder =
    DeviceSequenceCompanion Function({
      Value<String> contextId,
      Value<int> nextSequence,
      Value<int> rowid,
    });

class $$DeviceSequenceTableFilterComposer
    extends Composer<_$PosDatabase, $DeviceSequenceTable> {
  $$DeviceSequenceTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get contextId => $composableBuilder(
    column: $table.contextId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get nextSequence => $composableBuilder(
    column: $table.nextSequence,
    builder: (column) => ColumnFilters(column),
  );
}

class $$DeviceSequenceTableOrderingComposer
    extends Composer<_$PosDatabase, $DeviceSequenceTable> {
  $$DeviceSequenceTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get contextId => $composableBuilder(
    column: $table.contextId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get nextSequence => $composableBuilder(
    column: $table.nextSequence,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$DeviceSequenceTableAnnotationComposer
    extends Composer<_$PosDatabase, $DeviceSequenceTable> {
  $$DeviceSequenceTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get contextId =>
      $composableBuilder(column: $table.contextId, builder: (column) => column);

  GeneratedColumn<int> get nextSequence => $composableBuilder(
    column: $table.nextSequence,
    builder: (column) => column,
  );
}

class $$DeviceSequenceTableTableManager
    extends
        RootTableManager<
          _$PosDatabase,
          $DeviceSequenceTable,
          DeviceSequenceData,
          $$DeviceSequenceTableFilterComposer,
          $$DeviceSequenceTableOrderingComposer,
          $$DeviceSequenceTableAnnotationComposer,
          $$DeviceSequenceTableCreateCompanionBuilder,
          $$DeviceSequenceTableUpdateCompanionBuilder,
          (
            DeviceSequenceData,
            BaseReferences<
              _$PosDatabase,
              $DeviceSequenceTable,
              DeviceSequenceData
            >,
          ),
          DeviceSequenceData,
          PrefetchHooks Function()
        > {
  $$DeviceSequenceTableTableManager(
    _$PosDatabase db,
    $DeviceSequenceTable table,
  ) : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$DeviceSequenceTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$DeviceSequenceTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$DeviceSequenceTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> contextId = const Value.absent(),
                Value<int> nextSequence = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => DeviceSequenceCompanion(
                contextId: contextId,
                nextSequence: nextSequence,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String contextId,
                Value<int> nextSequence = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => DeviceSequenceCompanion.insert(
                contextId: contextId,
                nextSequence: nextSequence,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$DeviceSequenceTable, DeviceSequenceData>(table),
                  BaseReferences<
                    _$PosDatabase,
                    $DeviceSequenceTable,
                    DeviceSequenceData
                  >(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$DeviceSequenceTableProcessedTableManager =
    ProcessedTableManager<
      _$PosDatabase,
      $DeviceSequenceTable,
      DeviceSequenceData,
      $$DeviceSequenceTableFilterComposer,
      $$DeviceSequenceTableOrderingComposer,
      $$DeviceSequenceTableAnnotationComposer,
      $$DeviceSequenceTableCreateCompanionBuilder,
      $$DeviceSequenceTableUpdateCompanionBuilder,
      (
        DeviceSequenceData,
        BaseReferences<_$PosDatabase, $DeviceSequenceTable, DeviceSequenceData>,
      ),
      DeviceSequenceData,
      PrefetchHooks Function()
    >;
typedef $$ReceiptAttemptsTableCreateCompanionBuilder =
    ReceiptAttemptsCompanion Function({
      required String id,
      required String saleId,
      required String attemptedAt,
      required String status,
      Value<String?> message,
      Value<int> rowid,
    });
typedef $$ReceiptAttemptsTableUpdateCompanionBuilder =
    ReceiptAttemptsCompanion Function({
      Value<String> id,
      Value<String> saleId,
      Value<String> attemptedAt,
      Value<String> status,
      Value<String?> message,
      Value<int> rowid,
    });

final class $$ReceiptAttemptsTableReferences
    extends
        BaseReferences<_$PosDatabase, $ReceiptAttemptsTable, ReceiptAttempt> {
  $$ReceiptAttemptsTableReferences(
    super.$_db,
    super.$_table,
    super.$_typedResult,
  );

  static $LocalSalesTable _saleIdTable(_$PosDatabase db) =>
      db.localSales.createAlias('receipt_attempts__sale_id__local_sales__id');

  $$LocalSalesTableProcessedTableManager get saleId {
    final $_column = $_itemColumn<String>('sale_id')!;

    final manager = $$LocalSalesTableTableManager(
      $_db,
      $_db.localSales,
    ).filter((f) => f.id.sqlEquals($_column));
    final item = $_typedResult.readTableOrNull(_saleIdTable($_db));
    if (item == null) return manager;
    return ProcessedTableManager(
      manager.$state.copyWith(prefetchedData: [item]),
    );
  }
}

class $$ReceiptAttemptsTableFilterComposer
    extends Composer<_$PosDatabase, $ReceiptAttemptsTable> {
  $$ReceiptAttemptsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get attemptedAt => $composableBuilder(
    column: $table.attemptedAt,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get message => $composableBuilder(
    column: $table.message,
    builder: (column) => ColumnFilters(column),
  );

  $$LocalSalesTableFilterComposer get saleId {
    final $$LocalSalesTableFilterComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.saleId,
      referencedTable: $db.localSales,
      getReferencedColumn: (t) => t.id,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$LocalSalesTableFilterComposer(
            $db: $db,
            $table: $db.localSales,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return composer;
  }
}

class $$ReceiptAttemptsTableOrderingComposer
    extends Composer<_$PosDatabase, $ReceiptAttemptsTable> {
  $$ReceiptAttemptsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get attemptedAt => $composableBuilder(
    column: $table.attemptedAt,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get status => $composableBuilder(
    column: $table.status,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get message => $composableBuilder(
    column: $table.message,
    builder: (column) => ColumnOrderings(column),
  );

  $$LocalSalesTableOrderingComposer get saleId {
    final $$LocalSalesTableOrderingComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.saleId,
      referencedTable: $db.localSales,
      getReferencedColumn: (t) => t.id,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$LocalSalesTableOrderingComposer(
            $db: $db,
            $table: $db.localSales,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return composer;
  }
}

class $$ReceiptAttemptsTableAnnotationComposer
    extends Composer<_$PosDatabase, $ReceiptAttemptsTable> {
  $$ReceiptAttemptsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<String> get attemptedAt => $composableBuilder(
    column: $table.attemptedAt,
    builder: (column) => column,
  );

  GeneratedColumn<String> get status =>
      $composableBuilder(column: $table.status, builder: (column) => column);

  GeneratedColumn<String> get message =>
      $composableBuilder(column: $table.message, builder: (column) => column);

  $$LocalSalesTableAnnotationComposer get saleId {
    final $$LocalSalesTableAnnotationComposer composer = $composerBuilder(
      composer: this,
      getCurrentColumn: (t) => t.saleId,
      referencedTable: $db.localSales,
      getReferencedColumn: (t) => t.id,
      builder:
          (
            joinBuilder, {
            $addJoinBuilderToRootComposer,
            $removeJoinBuilderFromRootComposer,
          }) => $$LocalSalesTableAnnotationComposer(
            $db: $db,
            $table: $db.localSales,
            $addJoinBuilderToRootComposer: $addJoinBuilderToRootComposer,
            joinBuilder: joinBuilder,
            $removeJoinBuilderFromRootComposer:
                $removeJoinBuilderFromRootComposer,
          ),
    );
    return composer;
  }
}

class $$ReceiptAttemptsTableTableManager
    extends
        RootTableManager<
          _$PosDatabase,
          $ReceiptAttemptsTable,
          ReceiptAttempt,
          $$ReceiptAttemptsTableFilterComposer,
          $$ReceiptAttemptsTableOrderingComposer,
          $$ReceiptAttemptsTableAnnotationComposer,
          $$ReceiptAttemptsTableCreateCompanionBuilder,
          $$ReceiptAttemptsTableUpdateCompanionBuilder,
          (ReceiptAttempt, $$ReceiptAttemptsTableReferences),
          ReceiptAttempt,
          PrefetchHooks Function({bool saleId})
        > {
  $$ReceiptAttemptsTableTableManager(
    _$PosDatabase db,
    $ReceiptAttemptsTable table,
  ) : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$ReceiptAttemptsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$ReceiptAttemptsTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$ReceiptAttemptsTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> id = const Value.absent(),
                Value<String> saleId = const Value.absent(),
                Value<String> attemptedAt = const Value.absent(),
                Value<String> status = const Value.absent(),
                Value<String?> message = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => ReceiptAttemptsCompanion(
                id: id,
                saleId: saleId,
                attemptedAt: attemptedAt,
                status: status,
                message: message,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String id,
                required String saleId,
                required String attemptedAt,
                required String status,
                Value<String?> message = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => ReceiptAttemptsCompanion.insert(
                id: id,
                saleId: saleId,
                attemptedAt: attemptedAt,
                status: status,
                message: message,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$ReceiptAttemptsTable, ReceiptAttempt>(table),
                  $$ReceiptAttemptsTableReferences(db, table, e),
                ),
              )
              .toList(),
          prefetchHooksCallback: ({saleId = false}) {
            return PrefetchHooks(
              db: db,
              explicitlyWatchedTables: [],
              addJoins:
                  <
                    T extends TableManagerState<
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic,
                      dynamic
                    >
                  >(state) {
                    if (saleId) {
                      state = state.withJoin(
                        currentTable: table,
                        currentColumn: table.saleId,
                        referencedTable: $$ReceiptAttemptsTableReferences
                            ._saleIdTable(db),
                        referencedColumn: $$ReceiptAttemptsTableReferences
                            ._saleIdTable(db)
                            .id,
                      ) as T;
                    }

                    return state;
                  },
              getPrefetchedDataCallback: (items) async {
                return [];
              },
            );
          },
        ),
      );
}

typedef $$ReceiptAttemptsTableProcessedTableManager =
    ProcessedTableManager<
      _$PosDatabase,
      $ReceiptAttemptsTable,
      ReceiptAttempt,
      $$ReceiptAttemptsTableFilterComposer,
      $$ReceiptAttemptsTableOrderingComposer,
      $$ReceiptAttemptsTableAnnotationComposer,
      $$ReceiptAttemptsTableCreateCompanionBuilder,
      $$ReceiptAttemptsTableUpdateCompanionBuilder,
      (ReceiptAttempt, $$ReceiptAttemptsTableReferences),
      ReceiptAttempt,
      PrefetchHooks Function({bool saleId})
    >;
typedef $$SyncWorkersTableCreateCompanionBuilder =
    SyncWorkersCompanion Function({
      required String contextId,
      Value<String?> owner,
      Value<int> expiresAt,
      Value<int> rowid,
    });
typedef $$SyncWorkersTableUpdateCompanionBuilder =
    SyncWorkersCompanion Function({
      Value<String> contextId,
      Value<String?> owner,
      Value<int> expiresAt,
      Value<int> rowid,
    });

class $$SyncWorkersTableFilterComposer
    extends Composer<_$PosDatabase, $SyncWorkersTable> {
  $$SyncWorkersTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get contextId => $composableBuilder(
    column: $table.contextId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get owner => $composableBuilder(
    column: $table.owner,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get expiresAt => $composableBuilder(
    column: $table.expiresAt,
    builder: (column) => ColumnFilters(column),
  );
}

class $$SyncWorkersTableOrderingComposer
    extends Composer<_$PosDatabase, $SyncWorkersTable> {
  $$SyncWorkersTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get contextId => $composableBuilder(
    column: $table.contextId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get owner => $composableBuilder(
    column: $table.owner,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get expiresAt => $composableBuilder(
    column: $table.expiresAt,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$SyncWorkersTableAnnotationComposer
    extends Composer<_$PosDatabase, $SyncWorkersTable> {
  $$SyncWorkersTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get contextId =>
      $composableBuilder(column: $table.contextId, builder: (column) => column);

  GeneratedColumn<String> get owner =>
      $composableBuilder(column: $table.owner, builder: (column) => column);

  GeneratedColumn<int> get expiresAt =>
      $composableBuilder(column: $table.expiresAt, builder: (column) => column);
}

class $$SyncWorkersTableTableManager
    extends
        RootTableManager<
          _$PosDatabase,
          $SyncWorkersTable,
          SyncWorker,
          $$SyncWorkersTableFilterComposer,
          $$SyncWorkersTableOrderingComposer,
          $$SyncWorkersTableAnnotationComposer,
          $$SyncWorkersTableCreateCompanionBuilder,
          $$SyncWorkersTableUpdateCompanionBuilder,
          (
            SyncWorker,
            BaseReferences<_$PosDatabase, $SyncWorkersTable, SyncWorker>,
          ),
          SyncWorker,
          PrefetchHooks Function()
        > {
  $$SyncWorkersTableTableManager(_$PosDatabase db, $SyncWorkersTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$SyncWorkersTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$SyncWorkersTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$SyncWorkersTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> contextId = const Value.absent(),
                Value<String?> owner = const Value.absent(),
                Value<int> expiresAt = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => SyncWorkersCompanion(
                contextId: contextId,
                owner: owner,
                expiresAt: expiresAt,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String contextId,
                Value<String?> owner = const Value.absent(),
                Value<int> expiresAt = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => SyncWorkersCompanion.insert(
                contextId: contextId,
                owner: owner,
                expiresAt: expiresAt,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map(
                (e) => (
                  e.readTable<$SyncWorkersTable, SyncWorker>(table),
                  BaseReferences<_$PosDatabase, $SyncWorkersTable, SyncWorker>(
                    db,
                    table,
                    e,
                  ),
                ),
              )
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$SyncWorkersTableProcessedTableManager =
    ProcessedTableManager<
      _$PosDatabase,
      $SyncWorkersTable,
      SyncWorker,
      $$SyncWorkersTableFilterComposer,
      $$SyncWorkersTableOrderingComposer,
      $$SyncWorkersTableAnnotationComposer,
      $$SyncWorkersTableCreateCompanionBuilder,
      $$SyncWorkersTableUpdateCompanionBuilder,
      (
        SyncWorker,
        BaseReferences<_$PosDatabase, $SyncWorkersTable, SyncWorker>,
      ),
      SyncWorker,
      PrefetchHooks Function()
    >;

class $PosDatabaseManager {
  final _$PosDatabase _db;
  $PosDatabaseManager(this._db);
  $$LocalProductsTableTableManager get localProducts =>
      $$LocalProductsTableTableManager(_db, _db.localProducts);
  $$LocalCustomersTableTableManager get localCustomers =>
      $$LocalCustomersTableTableManager(_db, _db.localCustomers);
  $$StockSnapshotsTableTableManager get stockSnapshots =>
      $$StockSnapshotsTableTableManager(_db, _db.stockSnapshots);
  $$AccountSnapshotsTableTableManager get accountSnapshots =>
      $$AccountSnapshotsTableTableManager(_db, _db.accountSnapshots);
  $$SyncMetadataTableTableManager get syncMetadata =>
      $$SyncMetadataTableTableManager(_db, _db.syncMetadata);
  $$DownloadsTableTableManager get downloads =>
      $$DownloadsTableTableManager(_db, _db.downloads);
  $$DownloadPagesTableTableManager get downloadPages =>
      $$DownloadPagesTableTableManager(_db, _db.downloadPages);
  $$LocalEffectsTableTableManager get localEffects =>
      $$LocalEffectsTableTableManager(_db, _db.localEffects);
  $$LocalSalesTableTableManager get localSales =>
      $$LocalSalesTableTableManager(_db, _db.localSales);
  $$LocalSaleItemsTableTableManager get localSaleItems =>
      $$LocalSaleItemsTableTableManager(_db, _db.localSaleItems);
  $$LocalPaymentsTableTableManager get localPayments =>
      $$LocalPaymentsTableTableManager(_db, _db.localPayments);
  $$OutboxTableTableManager get outbox =>
      $$OutboxTableTableManager(_db, _db.outbox);
  $$DeviceSequenceTableTableManager get deviceSequence =>
      $$DeviceSequenceTableTableManager(_db, _db.deviceSequence);
  $$ReceiptAttemptsTableTableManager get receiptAttempts =>
      $$ReceiptAttemptsTableTableManager(_db, _db.receiptAttempts);
  $$SyncWorkersTableTableManager get syncWorkers =>
      $$SyncWorkersTableTableManager(_db, _db.syncWorkers);
}
